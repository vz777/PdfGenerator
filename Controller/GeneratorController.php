<?php

namespace PdfGenerator\Controller;

use PdfGenerator\PdfGenerator;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\Event\PdfEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Template\TemplateHelperInterface;
use Thelia\Core\Template\TheliaTemplateHelper;
use Thelia\Exception\TheliaProcessException;
use Thelia\Log\Tlog;



class GeneratorController extends BaseFrontController
{
    /** @var TemplateHelperInterface */
    protected $templateHelper;
    protected $eventDispatcher;
    
    public function __construct(TemplateHelperInterface $templateHelper, EventDispatcherInterface $eventDispatcher)
    {
        $this->templateHelper = $templateHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route("/getpdf/{template}/{outputFileName}", name="pdf_generator_get_pdf")
     */
    public function downloadPdf($template, $outputFileName)
    {
        return $this->renderPdfTemplate($template, $outputFileName, false);
    }
    /**
     * @Route("/viewpdf/{template}/{outputFileName}", name="pdf_generator_view_pdf")
     */
    public function viewPdf($template, $outputFileName)
    {
        return $this->renderPdfTemplate($template, $outputFileName, true);
    }
    /**
     * @param $templateName
     * @param $outputFileName
     * @param $browser
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderPdfTemplate($templateName, $outputFileName, $browser)
    {
        $html = $this->renderRaw(
            $templateName,
            [],
            $this->getTemplateHelper()->getActivePdfTemplate()
        );

        try {
            $pdfEvent = new PdfEvent($html);
            //$this->dispatch(TheliaEvents::GENERATE_PDF, $pdfEvent);
            //$eventDispatcher->dispatch($pdfEvent, TheliaEvents::GENERATE_PDF);
            //ununderstandble for me -- it should work better with the line above but it work the following line
            $this->eventDispatcher->dispatch($pdfEvent, TheliaEvents::GENERATE_PDF);
            if ($pdfEvent->hasPdf()) {
                return $this->pdfResponse($pdfEvent->getPdf(), $outputFileName, 200, $browser);
            }
        } catch (\Exception $e) {
            Tlog::getInstance()->error(
                sprintf(
                    'error during generating PDF document %s.html with message "%s"',
                    $templateName,
                    $e->getMessage()
                )
            );
        }

        throw new TheliaProcessException(
            //$translator->trans(

            $this->getTranslator()->trans(
                "We're sorry, this PDF document %name is not available at the moment.",
                [ '%name' => $outputFileName],
                PdfGenerator::DOMAIN_NAME
            )
        );
    }
}
