<?php

namespace Shopsys\FrameworkBundle\Model\Order\Status;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Shopsys\FrameworkBundle\Model\Localization\AbstractTranslatableEntity;

/**
 * @ORM\Table(name="order_statuses")
 * @ORM\Entity
 *
 * @method \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusTranslation translation(?string $locale = null)
 */
class OrderStatus extends AbstractTranslatableEntity
{
    public const TYPE_NEW = 1;
    public const TYPE_IN_PROGRESS = 2;
    public const TYPE_DONE = 3;
    public const TYPE_CANCELED = 4;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusTranslation[]|\Doctrine\Common\Collections\Collection
     *
     * @Prezent\Translations(targetEntity="Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusTranslation")
     */
    protected $translations;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $type;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusData $orderStatusData
     * @param int $type
     */
    public function __construct(OrderStatusData $orderStatusData, $type)
    {
        $this->translations = new ArrayCollection();
        $this->setType($type);
        $this->setTranslations($orderStatusData);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string|null $locale
     * @return string
     */
    public function getName($locale = null)
    {
        return $this->translation($locale)->getName();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusData $orderStatusData
     */
    protected function setTranslations(OrderStatusData $orderStatusData)
    {
        foreach ($orderStatusData->name as $locale => $name) {
            $this->translation($locale)->setName($name);
        }
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusTranslation
     */
    protected function createTranslation()
    {
        return new OrderStatusTranslation();
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    protected function setType($type)
    {
        if (in_array($type, [
            self::TYPE_NEW,
            self::TYPE_IN_PROGRESS,
            self::TYPE_DONE,
            self::TYPE_CANCELED,
        ], true)) {
            $this->type = $type;
        } else {
            throw new \Shopsys\FrameworkBundle\Model\Order\Status\Exception\InvalidOrderStatusTypeException($type);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusData $orderStatusData
     */
    public function edit(OrderStatusData $orderStatusData)
    {
        $this->setTranslations($orderStatusData);
    }

    public function checkForDelete()
    {
        if ($this->type !== self::TYPE_IN_PROGRESS) {
            throw new \Shopsys\FrameworkBundle\Model\Order\Status\Exception\OrderStatusDeletionForbiddenException($this);
        }
    }
}
