<?php

declare(strict_types=1);

namespace Shopsys\FrameworkBundle\Model\Customer\User;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddressFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\Customer;
use Shopsys\FrameworkBundle\Model\Customer\CustomerFacade;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFacade;
use Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade;
use Shopsys\FrameworkBundle\Model\Order\Order;

class CustomerUserFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserRepository
     */
    protected $customerUserRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateDataFactoryInterface
     */
    protected $customerUserUpdateDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade
     */
    protected $customerMailFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\BillingAddressFactoryInterface
     */
    protected $billingAddressFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactoryInterface
     */
    protected $billingAddressDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFactoryInterface
     */
    protected $customerUserFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserPasswordFacade
     */
    protected $customerUserPasswordFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CustomerFacade
     */
    protected $customerFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFacade
     */
    protected $deliveryAddressFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserRepository $customerUserRepository
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateDataFactoryInterface $customerUserUpdateDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade $customerMailFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddressFactoryInterface $billingAddressFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactoryInterface $billingAddressDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFactoryInterface $customerUserFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserPasswordFacade $customerUserPasswordFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFacade $deliveryAddressFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        CustomerUserRepository $customerUserRepository,
        CustomerUserUpdateDataFactoryInterface $customerUserUpdateDataFactory,
        CustomerMailFacade $customerMailFacade,
        BillingAddressFactoryInterface $billingAddressFactory,
        BillingAddressDataFactoryInterface $billingAddressDataFactory,
        CustomerUserFactoryInterface $customerUserFactory,
        CustomerUserPasswordFacade $customerUserPasswordFacade,
        CustomerFacade $customerFacade,
        DeliveryAddressFacade $deliveryAddressFacade
    ) {
        $this->em = $em;
        $this->customerUserRepository = $customerUserRepository;
        $this->customerUserUpdateDataFactory = $customerUserUpdateDataFactory;
        $this->customerMailFacade = $customerMailFacade;
        $this->billingAddressFactory = $billingAddressFactory;
        $this->billingAddressDataFactory = $billingAddressDataFactory;
        $this->customerUserFactory = $customerUserFactory;
        $this->customerUserPasswordFacade = $customerUserPasswordFacade;
        $this->customerFacade = $customerFacade;
        $this->deliveryAddressFacade = $deliveryAddressFacade;
    }

    /**
     * @param int $customerUserId
     *
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser
     */
    public function getCustomerUserById($customerUserId)
    {
        return $this->customerUserRepository->getCustomerUserById($customerUserId);
    }

    /**
     * @param string $email
     * @param int $domainId
     *
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser|null
     */
    public function findCustomerUserByEmailAndDomain($email, $domainId)
    {
        return $this->customerUserRepository->findCustomerUserByEmailAndDomain($email, $domainId);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserData $customerUserData
     *
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser
     */
    public function register(CustomerUserData $customerUserData)
    {
        $customer = $this->customerFacade->createCustomerWithBillingAddress($this->billingAddressDataFactory->create());
        $customerUser = $this->createCustomerUser($customer, $customerUserData);

        $this->customerMailFacade->sendRegistrationMail($customerUser);

        return $customerUser;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData
     *
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser
     */
    public function create(CustomerUserUpdateData $customerUserUpdateData)
    {
        $customer = $this->customerFacade->createCustomerWithBillingAddress($customerUserUpdateData->billingAddressData);
        $customerUser = $this->createCustomerUser($customer, $customerUserUpdateData->customerUserData);

        if ($customerUserUpdateData->deliveryAddressData && $customerUserUpdateData->deliveryAddressData->addressFilled) {
            $customerUserUpdateData->deliveryAddressData->customer = $customer;
            $deliveryAddress = $this->deliveryAddressFacade->create($customerUserUpdateData->deliveryAddressData);
            $customer->addDeliveryAddress($deliveryAddress);
        }

        if ($customerUserUpdateData->sendRegistrationMail) {
            $this->customerMailFacade->sendRegistrationMail($customerUser);
        }

        return $customerUser;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\Customer $customer
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserData $customerUserData
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser
     */
    protected function createCustomerUser(
        Customer $customer,
        CustomerUserData $customerUserData
    ): CustomerUser {
        $customerUserData->customer = $customer;
        $customerUser = $this->customerUserFactory->create($customerUserData);
        $this->setEmail($customerUserData->email, $customerUser);

        $this->em->persist($customerUser);
        $this->em->flush();

        return $customerUser;
    }

    /**
     * @param int $customerUserId
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress|null $deliveryAddress
     * @param bool $updateExistingDeliveryAddress
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser
     */
    protected function edit(
        int $customerUserId,
        CustomerUserUpdateData $customerUserUpdateData,
        ?DeliveryAddress $deliveryAddress,
        bool $updateExistingDeliveryAddress
    ) {
        $customerUser = $this->getCustomerUserById($customerUserId);
        $customerUserUpdateData->deliveryAddressData->customer = $customerUser->getCustomer();

        $customerUser->edit($customerUserUpdateData->customerUserData);

        if ($customerUserUpdateData->customerUserData->password !== null) {
            $this->customerUserPasswordFacade->changePassword($customerUser, $customerUserUpdateData->customerUserData->password);
        }

        $customerUser->getCustomer()->getBillingAddress()->edit($customerUserUpdateData->billingAddressData);

        if ($customerUserUpdateData->deliveryAddressData &&
            $customerUserUpdateData->deliveryAddressData->addressFilled &&
            $deliveryAddress === null
        ) {
            $this->deliveryAddressFacade->create($customerUserUpdateData->deliveryAddressData);
        } elseif ($updateExistingDeliveryAddress &&
            $customerUserUpdateData->deliveryAddressData &&
            $customerUserUpdateData->deliveryAddressData->addressFilled &&
            $deliveryAddress !== null
        ) {
            $this->deliveryAddressFacade->edit($deliveryAddress->getId(), $customerUserUpdateData->deliveryAddressData);
        }

        return $customerUser;
    }

    /**
     * @param int $customerUserId
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData
     *
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser
     */
    public function editByAdmin($customerUserId, CustomerUserUpdateData $customerUserUpdateData)
    {
        $customerUser = $this->edit($customerUserId, $customerUserUpdateData, null, false);

        $this->setEmail($customerUserUpdateData->customerUserData->email, $customerUser);

        $this->em->flush();

        return $customerUser;
    }

    /**
     * @param int $customerUserId
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress|null $deliveryAddress
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser
     */
    public function editByCustomerUser(int $customerUserId, CustomerUserUpdateData $customerUserUpdateData, ?DeliveryAddress $deliveryAddress)
    {
        $customerUser = $this->edit($customerUserId, $customerUserUpdateData, $deliveryAddress, true);

        $this->em->flush();

        return $customerUser;
    }

    /**
     * @param int $customerUserId
     */
    public function delete($customerUserId)
    {
        $customerUser = $this->getCustomerUserById($customerUserId);

        $this->em->remove($customerUser);
        $this->em->flush();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser $customerUser
     * @param \Shopsys\FrameworkBundle\Model\Order\Order $order
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress|null $deliveryAddress
     */
    public function amendCustomerUserDataFromOrder(CustomerUser $customerUser, Order $order, ?DeliveryAddress $deliveryAddress)
    {
        $this->edit(
            $customerUser->getId(),
            $this->customerUserUpdateDataFactory->createAmendedByOrder($customerUser, $order, $deliveryAddress),
            $deliveryAddress,
            false
        );

        $this->em->flush();
    }

    /**
     * @param string $email
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser $customerUser
     */
    protected function setEmail(string $email, CustomerUser $customerUser): void
    {
        $customerUserByEmailAndDomain = $this->findCustomerUserByEmailAndDomain(
            $email,
            $customerUser->getDomainId()
        );

        if ($customerUserByEmailAndDomain !== null && $customerUser->getId() !== $customerUserByEmailAndDomain->getId()) {
            throw new \Shopsys\FrameworkBundle\Model\Customer\Exception\DuplicateEmailException($email);
        }

        $customerUser->setEmail($email);
    }
}
