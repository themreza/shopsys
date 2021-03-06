<?php

namespace Shopsys\FrameworkBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Form\Admin\Mail\AllMailTemplatesFormType;
use Shopsys\FrameworkBundle\Form\Admin\Mail\MailSettingFormType;
use Shopsys\FrameworkBundle\Model\Customer\Mail\RegistrationMail;
use Shopsys\FrameworkBundle\Model\Customer\Mail\ResetPasswordMail;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplate;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateFacade;
use Shopsys\FrameworkBundle\Model\Mail\Setting\MailSettingFacade;
use Shopsys\FrameworkBundle\Model\Order\Mail\OrderMail;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatus;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusFacade;
use Shopsys\FrameworkBundle\Model\PersonalData\Mail\PersonalDataAccessMail;
use Shopsys\FrameworkBundle\Model\PersonalData\Mail\PersonalDataExportMail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MailController extends AdminBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\Mail\RegistrationMail
     */
    protected $registrationMail;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\Mail\ResetPasswordMail
     */
    protected $resetPasswordMail;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    protected $adminDomainTabsFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Mail\MailTemplateFacade
     */
    protected $mailTemplateFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Mail\Setting\MailSettingFacade
     */
    protected $mailSettingFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Mail\OrderMail
     */
    protected $orderMail;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusFacade
     */
    protected $orderStatusFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\PersonalData\Mail\PersonalDataAccessMail
     */
    protected $personalDataAccessMail;

    /**
     * @var \Shopsys\FrameworkBundle\Model\PersonalData\Mail\PersonalDataExportMail
     */
    protected $personalDataExportMail;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\ResetPasswordMail $resetPasswordMail
     * @param \Shopsys\FrameworkBundle\Model\Order\Mail\OrderMail $orderMail
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\RegistrationMail $registrationMail
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\FrameworkBundle\Model\Mail\MailTemplateFacade $mailTemplateFacade
     * @param \Shopsys\FrameworkBundle\Model\Mail\Setting\MailSettingFacade $mailSettingFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusFacade $orderStatusFacade
     * @param \Shopsys\FrameworkBundle\Model\PersonalData\Mail\PersonalDataAccessMail $personalDataAccessMail
     * @param \Shopsys\FrameworkBundle\Model\PersonalData\Mail\PersonalDataExportMail $personalDataExportMail
     */
    public function __construct(
        ResetPasswordMail $resetPasswordMail,
        OrderMail $orderMail,
        RegistrationMail $registrationMail,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        MailTemplateFacade $mailTemplateFacade,
        MailSettingFacade $mailSettingFacade,
        OrderStatusFacade $orderStatusFacade,
        PersonalDataAccessMail $personalDataAccessMail,
        PersonalDataExportMail $personalDataExportMail
    ) {
        $this->resetPasswordMail = $resetPasswordMail;
        $this->orderMail = $orderMail;
        $this->registrationMail = $registrationMail;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->mailTemplateFacade = $mailTemplateFacade;
        $this->mailSettingFacade = $mailSettingFacade;
        $this->orderStatusFacade = $orderStatusFacade;
        $this->personalDataAccessMail = $personalDataAccessMail;
        $this->personalDataExportMail = $personalDataExportMail;
    }

    /**
     * @return array
     */
    protected function getOrderStatusVariablesLabels()
    {
        return [
            OrderMail::VARIABLE_NUMBER => t('Order number'),
            OrderMail::VARIABLE_DATE => t('Date and time of order creation'),
            OrderMail::VARIABLE_URL => t('E-shop URL address'),
            OrderMail::VARIABLE_TRANSPORT => t('Chosen shipping name'),
            OrderMail::VARIABLE_PAYMENT => t('Chosen payment name'),
            OrderMail::VARIABLE_TOTAL_PRICE => t('Total order price (including VAT)'),
            OrderMail::VARIABLE_BILLING_ADDRESS => t(
                'Billing address - name, last name, company, company number, tax number and billing address'
            ),
            OrderMail::VARIABLE_DELIVERY_ADDRESS => t('Delivery address'),
            OrderMail::VARIABLE_NOTE => t('Note'),
            OrderMail::VARIABLE_PRODUCTS => t(
                'List of products in order (name, quantity, price per unit including VAT, total price per item including VAT)'
            ),
            OrderMail::VARIABLE_ORDER_DETAIL_URL => t('Order detail URL address'),
            OrderMail::VARIABLE_TRANSPORT_INSTRUCTIONS => t('Shipping instructions'),
            OrderMail::VARIABLE_PAYMENT_INSTRUCTIONS => t('Payment instructions'),
        ];
    }

    /**
     * @return array
     */
    protected function getRegistrationVariablesLabels()
    {
        return [
            RegistrationMail::VARIABLE_FIRST_NAME => t('First name'),
            RegistrationMail::VARIABLE_LAST_NAME => t('Last name'),
            RegistrationMail::VARIABLE_EMAIL => t('Email'),
            RegistrationMail::VARIABLE_URL => t('E-shop URL address'),
            RegistrationMail::VARIABLE_LOGIN_PAGE => t('Link to the log in page'),
        ];
    }

    /**
     * @return array
     */
    protected function getResetPasswordVariablesLabels()
    {
        return [
            ResetPasswordMail::VARIABLE_EMAIL => t('Email'),
            ResetPasswordMail::VARIABLE_NEW_PASSWORD_URL => t('New password settings URL address'),
        ];
    }

    /**
     * @return array
     */
    protected function getPersonalDataAccessVariablesLabels()
    {
        return [
            PersonalDataAccessMail::VARIABLE_DOMAIN => t('E-shop name'),
            PersonalDataAccessMail::VARIABLE_EMAIL => t('Email'),
            PersonalDataAccessMail::VARIABLE_URL => t('E-shop URL address'),
        ];
    }

    /**
     * @return array
     */
    protected function getPersonalExportVariablesLabels()
    {
        return [
            PersonalDataExportMail::VARIABLE_DOMAIN => t('E-shop name'),
            PersonalDataExportMail::VARIABLE_EMAIL => t('Email'),
            PersonalDataExportMail::VARIABLE_URL => t('E-shop URL address'),
        ];
    }

    /**
     * @return array
     */
    protected function getTemplateParameters()
    {
        $orderStatusesTemplateVariables = $this->orderMail->getTemplateVariables();
        $registrationTemplateVariables = $this->registrationMail->getTemplateVariables();
        $resetPasswordTemplateVariables = array_unique(array_merge(
            $this->resetPasswordMail->getBodyVariables(),
            $this->resetPasswordMail->getSubjectVariables()
        ));
        $resetPasswordTemplateRequiredVariables = array_unique(array_merge(
            $this->resetPasswordMail->getRequiredBodyVariables(),
            $this->resetPasswordMail->getRequiredSubjectVariables()
        ));

        $selectedDomainId = $this->adminDomainTabsFacade->getSelectedDomainId();
        $orderStatusMailTemplatesByOrderStatusId = $this->mailTemplateFacade->getOrderStatusMailTemplatesIndexedByOrderStatusId(
            $selectedDomainId
        );
        $registrationMailTemplate = $this->mailTemplateFacade->get(
            MailTemplate::REGISTRATION_CONFIRM_NAME,
            $selectedDomainId
        );
        $resetPasswordMailTemplate = $this->mailTemplateFacade->get(
            MailTemplate::RESET_PASSWORD_NAME,
            $selectedDomainId
        );
        $personalDataAccessTemplate = $this->mailTemplateFacade->get(
            MailTemplate::PERSONAL_DATA_ACCESS_NAME,
            $selectedDomainId
        );

        $personalDataExportTemplate = $this->mailTemplateFacade->get(
            MailTemplate::PERSONAL_DATA_EXPORT_NAME,
            $selectedDomainId
        );

        return [
            'orderStatusesIndexedById' => $this->orderStatusFacade->getAllIndexedById(),
            'orderStatusMailTemplatesByOrderStatusId' => $orderStatusMailTemplatesByOrderStatusId,
            'orderStatusVariables' => $orderStatusesTemplateVariables,
            'orderStatusVariablesLabels' => $this->getOrderStatusVariablesLabels(),
            'registrationMailTemplate' => $registrationMailTemplate,
            'registrationVariables' => $registrationTemplateVariables,
            'registrationVariablesLabels' => $this->getRegistrationVariablesLabels(),
            'resetPasswordMailTemplate' => $resetPasswordMailTemplate,
            'resetPasswordRequiredVariables' => $resetPasswordTemplateRequiredVariables,
            'resetPasswordVariables' => $resetPasswordTemplateVariables,
            'resetPasswordVariablesLabels' => $this->getResetPasswordVariablesLabels(),
            'TYPE_NEW' => OrderStatus::TYPE_NEW,
            'personalDataAccessTemplate' => $personalDataAccessTemplate,
            'personalDataAccessVariables' => $this->personalDataAccessMail->getSubjectVariables(),
            'personalDataAccessRequiredVariablesLabels' => $this->personalDataAccessMail->getRequiredBodyVariables(),
            'personalDataAccessVariablesLabels' => $this->getPersonalDataAccessVariablesLabels(),
            'personalDataExportTemplate' => $personalDataExportTemplate,
            'personalDataExportVariables' => $this->personalDataExportMail->getSubjectVariables(),
            'personalDataExportRequiredVariablesLabels' => $this->personalDataExportMail->getRequiredBodyVariables(),
            'personalDataExportVariablesLabels' => $this->getPersonalExportVariablesLabels(),
        ];
    }

    /**
     * @Route("/mail/template/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function templateAction(Request $request)
    {
        $allMailTemplatesData = $this->mailTemplateFacade->getAllMailTemplatesDataByDomainId(
            $this->adminDomainTabsFacade->getSelectedDomainId()
        );

        $form = $this->createForm(AllMailTemplatesFormType::class, $allMailTemplatesData);
        $form->handleRequest($request);
        $allMailTemplatesData->getAllTemplates();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->mailTemplateFacade->saveMailTemplatesData(
                $allMailTemplatesData->getAllTemplates(),
                $allMailTemplatesData->domainId
            );

            $this->addSuccessFlash(t('Email templates settings modified'));

            return $this->redirectToRoute('admin_mail_template');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        $templateParameters = $this->getTemplateParameters();
        $templateParameters['form'] = $form->createView();

        return $this->render('@ShopsysFramework/Admin/Content/Mail/template.html.twig', $templateParameters);
    }

    /**
     * @Route("/mail/setting/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function settingAction(Request $request)
    {
        $selectedDomainId = $this->adminDomainTabsFacade->getSelectedDomainId();

        $mailSettingData = [
            'email' => $this->mailSettingFacade->getMainAdminMail($selectedDomainId),
            'name' => $this->mailSettingFacade->getMainAdminMailName($selectedDomainId),
        ];

        $form = $this->createForm(MailSettingFormType::class, $mailSettingData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mailSettingData = $form->getData();

            $this->mailSettingFacade->setMainAdminMail($mailSettingData['email'], $selectedDomainId);
            $this->mailSettingFacade->setMainAdminMailName($mailSettingData['name'], $selectedDomainId);

            $this->addSuccessFlash(t('Email settings modified.'));
        }

        return $this->render('@ShopsysFramework/Admin/Content/Mail/setting.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
