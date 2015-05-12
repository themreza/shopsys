<?php

namespace SS6\ShopBundle\Model\AdvancedSearchOrder\Filter;

use Doctrine\ORM\QueryBuilder;
use SS6\ShopBundle\Form\FormType;
use SS6\ShopBundle\Model\AdvancedSearch\AdvancedSearchFilterInterface;
use Symfony\Component\Validator\Constraints\DateTime;

class OrderCreateDateFilter implements AdvancedSearchFilterInterface {

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'orderCreatedAt';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAllowedOperators() {
		return [
			self::OPERATOR_AFTER,
			self::OPERATOR_BEFORE,
			self::OPERATOR_IS,
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getValueFormType() {
		return FormType::DATE_PICKER;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getValueFormOptions() {
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function extendQueryBuilder(QueryBuilder $queryBuilder, $rulesData) {
		foreach ($rulesData as $index => $ruleData) {
			if ($ruleData->operator === self::OPERATOR_AFTER || $ruleData->operator === self::OPERATOR_BEFORE ||
				$ruleData->operator === self::OPERATOR_IS) {
				if ($ruleData->value === null || empty($ruleData->value)) {
					$searchValue = new \DateTime();
				} else {
					$searchValue = $ruleData->value;
				}

				$dqlOperator = $this->getContainsDqlOperator($ruleData->operator);
				$parameterName = 'orderCreatedAt_' . $index;
				$parameterName2 = 'orderCreatedAt_' . $index . '_2';

				$where = 'o.createdAt ' . $dqlOperator . ' :' . $parameterName;

				if ($ruleData->operator === self::OPERATOR_IS) {
					/** @var $searchValue \DateTime */
					$searchValue2 = clone $searchValue;
					$searchValue2 = $searchValue2->modify('+1 day')->format('Y-m-d');
					$searchValue = $searchValue->format('Y-m-d');
					$where = 'o.createdAt ' . $dqlOperator . ' :' . $parameterName . ' AND :' . $parameterName2;
				}

				$queryBuilder->andWhere($where);
				$queryBuilder->setParameter($parameterName, $searchValue);
				if ($ruleData->operator === self::OPERATOR_IS) {
					$queryBuilder->setParameter($parameterName2, $searchValue2);
				}
			}
		}
	}

	/**
	 * @param string $operator
	 * @return string
	 */
	private function getContainsDqlOperator($operator) {
		switch ($operator) {
			case self::OPERATOR_AFTER:
				return '>=';
			case self::OPERATOR_BEFORE:
				return '<';
			case self::OPERATOR_IS:
				return 'BETWEEN';
		}
	}
}