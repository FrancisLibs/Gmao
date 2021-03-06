<?php

namespace App\Controller;

use App\Entity\Connexion;
use App\Entity\Workorder;
use App\Entity\StockValue;
use App\Repository\PartRepository;
use App\Repository\UserRepository;
use App\Repository\ParamsRepository;
use App\Repository\TemplateRepository;
use App\Repository\WorkorderRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\WorkorderStatusRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    private $paramsRepository;
    private $workorderRepository;
    private $templateRepository;
    private $workorderStatusRepository;
    private $userRepository;
    private $manager;
    private $partRepository;


    public function __construct(
        EntityManagerInterface $manager,
        TemplateRepository $templateRepository,
        WorkorderRepository $workorderRepository,
        ParamsRepository $paramsRepository,
        WorkorderStatusRepository $workorderStatusRepository,
        UserRepository $userRepository,
        PartRepository $partRepository
    ) {
        $this->paramsRepository = $paramsRepository;
        $this->workorderRepository = $workorderRepository;
        $this->templateRepository = $templateRepository;
        $this->manager = $manager;
        $this->workorderStatusRepository = $workorderStatusRepository;
        $this->userRepository = $userRepository;
        $this->partRepository = $partRepository;
    }

    /**
     * @Route("/", name="home")
     * @Security("is_granted('ROLE_USER')")
     */
    public function index(MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        $organisation = $user->getOrganisation();
        $organisationId = $organisation->getId();
        $serviceId = $user->getService()->getId();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Cr??ation d'un enregistrement des connexions
        if ($user) {
            $connexion = new Connexion();
            $connexion
                ->setDate(new \DateTime())
                ->setUser($user);

            $this->manager->persist($connexion);
            $this->manager->flush();
        }

        // Lecture des dates de v??rification cherch??es dans le fichier des param??tres
        $params = $this->paramsRepository->find(1);
        $lastPreventiveDate = $params->getLastPreventiveDate()->getTimestamp();
        $lastStockValueDate = $params->getLastStockValueDate()->getTimestamp();
        $today = (new \DateTime())->getTimestamp();

        // Gestion des bons de travail pr??ventifs
        // On v??rifie ?? chaque connexion  puis rajout d'1 jour ?? la date enregistr??e
        $lastPreventiveDate = $lastPreventiveDate + 24 * 60 * 60;
        if ($lastPreventiveDate <= $today) {
            // D??finition de la prochaine date ?? celle d'aujourd'hui
            $params->setLastPreventiveDate(new \DateTime());
            $this->manager->persist($params);

            $this->preventiveProcessing($organisationId, $today);

            $this->setPreventiveStatus($organisationId, $today);
        }

        // Gestion de l'enregistrement de la valeur du stock, une fois par semaine-------

        $lastStockValueDate = $lastStockValueDate + (60 * 60 * 24 * 7);
        if ($today >= $lastStockValueDate) { // Si la date du jour est >= d'une semaine ?? l'ancienne date
            // Calcul du montant du stock
            $totalStock = $this->partRepository->findTotalStock($organisation);

            // Cr??ation de l'enregistrement
            $stockValue = new StockValue();
            $stockValue->setValue($totalStock)
                ->setDate(new \Datetime())
                ->setOrganisation($organisation);
            $this->manager->persist($stockValue);

            // Calcul nouvelle date dans le fichier params
            $params->setLastStockValueDate(new \DateTime());

            $this->manager->flush();
        }

        // ------------------------------------------------------------------------------
        // R??cup??ration des utilisateurs pour l'affichage des photos
        // Par organisation ET service
        $users = $this->userRepository->findBy(
            [
                'organisation' => $organisationId,
                'service' => $serviceId,
                'active' => true,
            ],
        );
        return $this->render('default/index.html.twig', [
            'users'         => $users,
        ]);
    }

    private function preventiveProcessing($organisationId, $today)
    {
        // Recherche des templates pr??ventifs
        $templates = $this->templateRepository->findAllTemplates($organisationId);

        foreach ($templates as $template) {
            // Prochaine date en secondes
            $nextDate = $template->getNextDate()->getTimestamp(); // Date de r??alisation
            // Jours avant la date en secondes
            $secondsBefore = $template->getDaysBefore() * 24 * 60 * 60; // Jours avant r??alisation
            // Date finale ?? prende en compte
            $nextCalculateDate = $nextDate - $secondsBefore; // Date finale d'activation en secondes

            // Test si template ??ligible
            if ($nextCalculateDate <= $today) {
                // Contr??le si BT pr??ventif n'est pas d??j?? actif
                if (!$this->workorderRepository->countPreventiveWorkorder($template->getTemplateNumber())) {
                    // Cr??ation du BT pr??ventif, en r??cup??rant les infos sur le template pr??ventif
                    $workorder = new Workorder();
                    $workorder->setCreatedAt(new \DateTime())
                        ->setPreventiveDate($template->getNextDate())
                        ->setRequest($template->getRequest())
                        ->setRemark($template->getRemark())
                        ->setOrganisation($template->getOrganisation())
                        ->setTemplateNumber($template->getTemplateNumber())
                        ->setUser($template->getUser())
                        ->setType(Workorder::PREVENTIF)
                        ->setPreventive(true)
                        ->setDaysBeforeLate($template->getDaysBeforeLate());
                    if ($template->getDaysBefore() > 0) {
                        $status = $this->workorderStatusRepository->findOneBy(['name' => 'EN_PREP.']);
                    } else {
                        $status = $this->workorderStatusRepository->findOneBy(['name' => 'EN_COURS']);
                    }
                    $workorder->setWorkorderStatus($status);
                    $machines = $template->getMachines();
                    foreach ($machines as $machine) {
                        $workorder->addMachine($machine);
                    }

                    $this->manager->persist($workorder);
                }
                $this->manager->flush();
            }
        }
        return;
    }

    // Pour l'??volution du BT dans le temps et g??rer son ??tat : modification du statut...
    private function setPreventiveStatus($organisation, $today)
    {
        $preventiveWorkorders = $this->workorderRepository->findAllPreventiveWorkorders($organisation);
        if ($preventiveWorkorders) {
            foreach ($preventiveWorkorders as $workorder) {
                $today = (new \Datetime())->getTimeStamp();
                $preventiveDate = $workorder->getPreventiveDate()->getTimeStamp();
                $daysBeforeLate = $workorder->getDaysBeforeLate() * 24 * 60 * 60;
                $dateBerforeLate = $preventiveDate + $daysBeforeLate;

                if ($today < $preventiveDate) {
                    $status = $this->workorderStatusRepository->findOneByName('EN_PREP.');
                } elseif ($today >= $preventiveDate && $today < $dateBerforeLate) {
                    $status = $this->workorderStatusRepository->findOneByName('EN_COURS');
                } elseif ($today > $dateBerforeLate) {
                    $status = $this->workorderStatusRepository->findOneByName('EN_RETARD');
                }

                $workorder->setWorkorderStatus($status);
                $this->manager->persist($workorder);
            }
            $this->manager->flush();
        }
    }
}
