<?php

declare(strict_types=1);

namespace Kajona\Flow\System;

use AGP\Agp_Commons\System\ArtemeonCommon;
use Kajona\System\System\Exception;
use Kajona\System\System\Exceptions\InvalidJsonFormatException;
use Kajona\System\System\JsonDecoder;
use Kajona\System\System\Lifecycle\ServiceLifeCycleInterface;
use Kajona\System\System\Lifecycle\ServiceLifeCycleUpdateException;
use Throwable;

/**
 * Helper class to simplify flow setup code in the main module's installer.
 *
 * @since 7.1
 */
final class FlowInstallerHelper
{
    /**
     * @var FlowManager
     */
    private $flowManager;

    /**
     * @var ServiceLifeCycleInterface
     */
    private $flowConfigLifeCycle;

    /**
     * @var ServiceLifeCycleInterface
     */
    private $flowStatusLifeCycle;

    /**
     * @var ServiceLifeCycleInterface
     */
    private $flowTransitionLifeCycle;

    /**
     * @var ServiceLifeCycleInterface
     */
    private $flowConditionLifeCycle;

    /**
     * @var ServiceLifeCycleInterface
     */
    private $flowActionLifeCycle;

    public function __construct(
        FlowManager $flowManager,
        ServiceLifeCycleInterface $flowConfigLifeCycle,
        ServiceLifeCycleInterface $flowStatusLifeCycle,
        ServiceLifeCycleInterface $flowTransitionLifeCycle,
        ServiceLifeCycleInterface $flowConditionLifeCycle,
        ServiceLifeCycleInterface $flowActionLifeCycle
    ) {
        $this->flowManager = $flowManager;
        $this->flowConfigLifeCycle = $flowConfigLifeCycle;
        $this->flowStatusLifeCycle = $flowStatusLifeCycle;
        $this->flowTransitionLifeCycle = $flowTransitionLifeCycle;
        $this->flowConditionLifeCycle = $flowConditionLifeCycle;
        $this->flowActionLifeCycle = $flowActionLifeCycle;
    }

    /**
     * @param string $targetClassName
     * @throws Exception
     */
    public function ensureTargetedFlowAvailability(string $targetClassName): void
    {
        if ($this->flowManager->isFlowConfiguredForClass($targetClassName)) {
            return;
        }

        FlowConfig::syncHandler();
        $this->setStatusOfTargetedFlowToCompleted($targetClassName);

        if (!$this->flowManager->isFlowConfiguredForClass($targetClassName)) {
            throw new \RuntimeException('unable to ensure targeted flow availability');
        }
    }

    /**
     * @param string $targetClassName
     * @throws Exception
     */
    public function ensureTargetedFlowAvailabilityWithoutValidatingConsistency(string $targetClassName): void
    {
        if ($this->flowManager->isFlowConfiguredForClass($targetClassName)) {
            return;
        }

        FlowConfig::syncHandler();
        $this->setStatusOfTargetedFlowToCompletedWithoutValidatingConsistency($targetClassName);

        if (!$this->flowManager->isFlowConfiguredForClass($targetClassName)) {
            throw new \RuntimeException('unable to ensure targeted flow availability');
        }
    }

    /**
     * @param string $targetClassName
     * @return FlowConfig
     * @throws Exception
     */
    public function getTargetedFlow(string $targetClassName): FlowConfig
    {
        $this->ensureTargetedFlowAvailability($targetClassName);

        return $this->flowManager->getFlowForClass($targetClassName);
    }

    /**
     * @param string $targetClassName
     * @return FlowConfig
     * @throws Exception
     */
    public function getTargetedFlowWithoutValidatingConsistency(string $targetClassName): FlowConfig
    {
        $this->ensureTargetedFlowAvailabilityWithoutValidatingConsistency($targetClassName);

        return $this->flowManager->getFlowForClass($targetClassName);
    }

    /**
     * @param FlowConfig $flow
     * @throws Exception
     */
    public function setStatusOfFlowToCompleted(FlowConfig $flow): void
    {
        $flow->setIntRecordStatus(ArtemeonCommon::INT_STATUS_RELEASED);
        $this->flowConfigLifeCycle->update($flow);
    }

    /**
     * @param FlowConfig $flow
     * @throws Exception
     */
    public function setStatusOfFlowToCompletedWithoutValidatingConsistency(FlowConfig $flow): void
    {
        $flow->setIntRecordStatus(ArtemeonCommon::INT_STATUS_RELEASED);
        $flow->setBitValidateConsistency(false);
        $this->flowConfigLifeCycle->update($flow);
    }

    /**
     * @param string $targetClassName
     * @throws Exception
     */
    public function setStatusOfTargetedFlowToCompleted(string $targetClassName): void
    {
        /** @var FlowConfig $flow */
        $flow = FlowConfig::getSingleObjectFiltered(
            FlowConfigFilter::createForTargetClass($targetClassName)
        );

        if ($flow instanceof FlowConfig) {
            $this->setStatusOfFlowToCompleted($flow);
        }
    }

    /**
     * @param string $targetClassName
     * @throws Exception
     */
    public function setStatusOfTargetedFlowToCompletedWithoutValidatingConsistency(string $targetClassName): void
    {
        /** @var FlowConfig $flow */
        $flow = FlowConfig::getSingleObjectFiltered(
            FlowConfigFilter::createForTargetClass($targetClassName)
        );

        if ($flow instanceof FlowConfig) {
            $this->setStatusOfFlowToCompletedWithoutValidatingConsistency($flow);
        }
    }

    /**
     * @param FlowConfig $flow
     * @param int $flowStatusIndex
     * @return FlowStatus
     * @throws \RuntimeException
     */
    public function getFlowStatusByIndex(FlowConfig $flow, int $flowStatusIndex): FlowStatus
    {
        $flowStatus = $flow->getStatusByIndex($flowStatusIndex);
        if (!($flowStatus instanceof FlowStatus)) {
            throw new \RuntimeException(
                \sprintf('unable to find flow status with index %d', $flowStatusIndex)
            );
        }

        return $flowStatus;
    }

    public function hasFlowStatusWithName(FlowConfig $flow, string $flowStatusName): bool
    {
        $flowStatus = $flow->getStatusByName($flowStatusName);

        return $flowStatus instanceof FlowStatus;
    }

    /**
     * @param FlowConfig $flow
     * @param string $flowStatusName
     * @return FlowStatus
     * @throws \RuntimeException
     */
    public function getFlowStatusByName(FlowConfig $flow, string $flowStatusName): FlowStatus
    {
        $flowStatus = $flow->getStatusByName($flowStatusName);
        if (!($flowStatus instanceof FlowStatus)) {
            throw new \RuntimeException(
                \sprintf('unable to find flow status with name "%s"', $flowStatusName)
            );
        }

        return $flowStatus;
    }

    /**
     * @param FlowStatus $flowStatus
     * @throws Exception
     */
    public function setStatusOfFlowStatusToCompleted(FlowStatus $flowStatus): void
    {
        $flowStatus->setIntRecordStatus(ArtemeonCommon::INT_STATUS_RELEASED);
        $this->flowStatusLifeCycle->update($flowStatus);
    }

    /**
     * @param FlowConfig $flow
     * @param string $name
     * @param string $color
     * @param array $roleRights
     * @return FlowStatus
     * @throws Exception
     */
    public function createFlowStatus(FlowConfig $flow, string $name, string $color, ?array $roleRights): FlowStatus
    {
        $flowStatus = new FlowStatus();
        $flowStatus->setStrName($name);
        $flowStatus->setStrIconColor($color);
        if (isset($roleRights)) {
            $flowStatus->setRoles($roleRights);
        }

        $this->flowStatusLifeCycle->update($flowStatus, $flow->getStrSystemid());

        return $flowStatus;
    }

    /**
     * @param FlowConfig $flow
     * @param string $originalFlowStatusName
     * @param string $newFlowStatusName
     * @throws \RuntimeException
     * @throws ServiceLifeCycleUpdateException
     */
    public function renameFlowStatus(FlowConfig $flow, string $originalFlowStatusName, string $newFlowStatusName): void
    {
        $flowStatus = $this->getFlowStatusByName($flow, $originalFlowStatusName);
        $flowStatus->setStrName($newFlowStatusName);

        $this->flowStatusLifeCycle->update($flowStatus);
    }

    /**
     * @param FlowStatus $sourceFlowStatus
     * @param FlowStatus $targetFlowStatus
     * @param callable|null $flowTransitionCallback
     * @throws Exception
     */
    public function createTransitionBetweenFlowStatuses(FlowStatus $sourceFlowStatus, FlowStatus $targetFlowStatus, callable $flowTransitionCallback = null): void
    {
        $flowTransition = new FlowTransition();
        $flowTransition->setStrTargetStatus($targetFlowStatus->getStrSystemid());

        $sourceFlowStatus->addTransition($flowTransition);

        if (isset($flowTransitionCallback)) {
            $this->withTransitionBetweenFlowStatuses($sourceFlowStatus, $targetFlowStatus, $flowTransitionCallback);
        }
    }

    /**
     * @param FlowStatus $sourceFlowStatus
     * @param FlowStatus $targetFlowStatus
     * @throws Exception
     */
    public function deleteTransitionBetweenFlowStatuses(FlowStatus $sourceFlowStatus, FlowStatus $targetFlowStatus): void
    {
        $flowTransition = $sourceFlowStatus->getTransitionByTargetIndex($targetFlowStatus->getIntIndex());

        if ($flowTransition instanceof FlowTransition) {
            $this->flowTransitionLifeCycle->delete($flowTransition);
        }
    }

    /**
     * @param FlowStatus $sourceFlowStatus
     * @param FlowStatus $targetFlowStatus
     * @throws Exception
     */
    public function hideTransitionBetweenFlowStatuses(FlowStatus $sourceFlowStatus, FlowStatus $targetFlowStatus): void
    {
        $flowTransition = $sourceFlowStatus->getTransitionByTargetIndex($targetFlowStatus->getIntIndex());

        if ($flowTransition instanceof FlowTransition) {
            $flowTransition->setIntVisible(0);
            $this->flowTransitionLifeCycle->update($flowTransition);
        }
    }

    /**
     * @throws Exception
     */
    public function markTransitionBetweenFlowStatusesAsSkipped(FlowStatus $sourceFlowStatus, FlowStatus $targetFlowStatus): void
    {
        $flowTransition = $sourceFlowStatus->getTransitionByTargetIndex($targetFlowStatus->getIntIndex());

        if ($flowTransition instanceof FlowTransition) {
            $flowTransition->setIntSkip(1);
            $this->flowTransitionLifeCycle->update($flowTransition);
        }
    }

    /**
     * @throws Exception
     */
    public function markTransitionBetweenFlowStatusesAsNotSkipped(FlowStatus $sourceFlowStatus, FlowStatus $targetFlowStatus): void
    {
        $flowTransition = $sourceFlowStatus->getTransitionByTargetIndex($targetFlowStatus->getIntIndex());

        if ($flowTransition instanceof FlowTransition) {
            $flowTransition->setIntSkip(0);
            $this->flowTransitionLifeCycle->update($flowTransition);
        }
    }

    public function withTransitionBetweenFlowStatuses(FlowStatus $sourceFlowStatus, FlowStatus $targetFlowStatus, callable $flowTransitionCallback): void
    {
        $flowTransition = $sourceFlowStatus->getTransitionByTargetIndex($targetFlowStatus->getIntIndex());

        if ($flowTransition instanceof FlowTransition) {
            $flowTransitionCallback($flowTransition);
        }
    }

    public function updateSourceFlowStatusOfTransitionBetweenFlowStatuses(
        FlowStatus $originalSourceFlowStatus,
        FlowStatus $originalTargetFlowStatus,
        FlowStatus $newSourceFlowStatus
    ): void {
        $this->withTransitionBetweenFlowStatuses(
            $originalSourceFlowStatus,
            $originalTargetFlowStatus,
            function (FlowTransition $flowTransition) use ($newSourceFlowStatus): void {
                $this->flowTransitionLifeCycle->update($flowTransition, $newSourceFlowStatus->getStrSystemid());
            }
        );
    }

    public function updateTargetFlowStatusOfTransitionBetweenFlowStatuses(
        FlowStatus $originalSourceFlowStatus,
        FlowStatus $originalTargetFlowStatus,
        FlowStatus $newTargetFlowStatus
    ): void {
        $this->withTransitionBetweenFlowStatuses(
            $originalSourceFlowStatus,
            $originalTargetFlowStatus,
            function (FlowTransition $flowTransition) use ($newTargetFlowStatus): void {
                $flowTransition->setStrTargetStatus($newTargetFlowStatus->getStrSystemid());
                $this->flowTransitionLifeCycle->update($flowTransition);
            }
        );
    }

    /**
     * @param FlowStatus $sourceFlowStatus
     * @param FlowStatus $targetFlowStatus
     * @throws ServiceLifeCycleUpdateException
     */
    public function reverseDirectionOfTransitionBetweenFlowStatuses(FlowStatus $sourceFlowStatus, FlowStatus $targetFlowStatus): void
    {
        $flowTransition = $sourceFlowStatus->getTransitionByTargetIndex($targetFlowStatus->getIntIndex());

        if ($flowTransition instanceof FlowTransition) {
            $flowTransition->setStrTargetStatus($sourceFlowStatus->getStrSystemid());
            $this->flowTransitionLifeCycle->update($flowTransition, $targetFlowStatus->getStrSystemid());
        }
    }

    private function encodeJsonParameters(array $parameters): string
    {
        /** @noinspection PhpComposerExtensionStubsInspection */
        return \json_encode(
            $parameters,
            \JSON_FORCE_OBJECT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
            512
        );
    }

    /**
     * @param FlowTransition $flowTransition
     * @param string $flowConditionClassName
     * @param array $flowConditionParameters
     * @return FlowConditionInterface
     * @throws Exception
     */
    public function createFlowCondition(FlowTransition $flowTransition, string $flowConditionClassName, array $flowConditionParameters = []): FlowConditionInterface
    {
        if (!\is_subclass_of($flowConditionClassName, FlowConditionAbstract::class)) {
            throw new \LogicException(
                \sprintf('unable to create flow action with class "%s"', $flowConditionClassName)
            );
        }

        /** @var FlowConditionAbstract $flowCondition */
        $flowCondition = new $flowConditionClassName();
        $flowCondition->setStrParams(
            $this->encodeJsonParameters($flowConditionParameters)
        );
        $this->flowConditionLifeCycle->update($flowCondition, $flowTransition->getStrSystemid());

        return $flowCondition;
    }

    public function deleteFlowTransitionConditionsOfType(FlowTransition $flowTransition, string $flowConditionClassName): void
    {
        $this->withFlowTransitionConditionsOfType($flowTransition, $flowConditionClassName, function (FlowConditionAbstract $flowCondition): void {
            $this->flowConditionLifeCycle->delete($flowCondition);
        });
    }

    public function hasFlowTransitionConditionsOfType(FlowTransition $flowTransition, string $flowConditionClassName): bool
    {
        foreach ($flowTransition->getArrConditions() as $flowCondition) {
            if ($flowCondition instanceof $flowConditionClassName) {
                return true;
            }
        }

        return false;
    }

    public function withFlowTransitionConditionsOfType(FlowTransition $flowTransition, string $flowConditionClassName, callable $flowConditionUpdateCallback): void
    {
        foreach ($flowTransition->getArrConditions() as $flowCondition) {
            if ($flowCondition instanceof $flowConditionClassName) {
                $flowConditionUpdateCallback($flowCondition);
            }
        }
    }

    /**
     * @param FlowConditionAbstract $flowCondition
     * @param callable $parameterUpdateCallback
     * @throws InvalidJsonFormatException
     * @throws ServiceLifeCycleUpdateException
     */
    public function updateParametersOfFlowCondition(FlowConditionAbstract $flowCondition, callable $parameterUpdateCallback): void
    {
        $originalParameters = JsonDecoder::decode($flowCondition->getStrParams());
        try {
            $updatedParameters = $parameterUpdateCallback($originalParameters);
        } catch (Throwable $exception) {
            throw new \RuntimeException('unable to update flow condition parameters', 0, $exception);
        }

        $flowCondition->setStrParams(
            $this->encodeJsonParameters($updatedParameters)
        );
        $this->flowConditionLifeCycle->update($flowCondition);
    }

    /**
     * @param FlowTransition $flowTransition
     * @param string $flowActionClassName
     * @param array $flowActionParameters
     * @return FlowActionInterface
     * @throws Exception
     */
    public function createFlowAction(FlowTransition $flowTransition, string $flowActionClassName, array $flowActionParameters = []): FlowActionInterface
    {
        if (!\is_subclass_of($flowActionClassName, FlowActionAbstract::class)) {
            throw new \LogicException(
                \sprintf('unable to create flow action with class "%s"', $flowActionClassName)
            );
        }

        /** @var FlowActionAbstract $flowAction */
        $flowAction = new $flowActionClassName();
        $flowAction->setStrParams(
            $this->encodeJsonParameters($flowActionParameters)
        );
        $this->flowActionLifeCycle->update($flowAction, $flowTransition->getStrSystemid());

        return $flowAction;
    }

    public function deleteFlowTransitionActionsOfType(FlowTransition $flowTransition, string $flowActionClassName): void
    {
        $this->withFlowTransitionActionsOfType($flowTransition, $flowActionClassName, function (FlowActionAbstract $flowAction): void {
            $this->flowActionLifeCycle->delete($flowAction);
        });
    }

    public function hasFlowTransitionActionsOfType(FlowTransition $flowTransition, string $flowActionClassName): bool
    {
        foreach ($flowTransition->getArrActions() as $flowAction) {
            if ($flowAction instanceof $flowActionClassName) {
                return true;
            }
        }

        return false;
    }

    public function withFlowTransitionActionsOfType(FlowTransition $flowTransition, string $flowActionClassName, callable $flowActionUpdateCallback): void
    {
        foreach ($flowTransition->getArrActions() as $flowAction) {
            if ($flowAction instanceof $flowActionClassName) {
                $flowActionUpdateCallback($flowAction);
            }
        }
    }

    /**
     * @param FlowActionAbstract $flowAction
     * @param callable $parameterUpdateCallback
     * @throws InvalidJsonFormatException
     * @throws ServiceLifeCycleUpdateException
     */
    public function updateParametersOfFlowAction(FlowActionAbstract $flowAction, callable $parameterUpdateCallback): void
    {
        $originalParameters = JsonDecoder::decode($flowAction->getStrParams());
        try {
            $updatedParameters = $parameterUpdateCallback($originalParameters);
        } catch (Throwable $exception) {
            throw new \RuntimeException('unable to update flow action parameters', 0, $exception);
        }

        $flowAction->setStrParams(
            $this->encodeJsonParameters($updatedParameters)
        );
        $this->flowActionLifeCycle->update($flowAction);
    }

    /**
     * @param FlowActionAbstract $flowAction
     * @param string $inPropertyName
     * @param string[] $evaluationTypeClassNames
     * @throws InvalidJsonFormatException
     * @throws ServiceLifeCycleUpdateException
     */
    public function updateEvaluationTypesOfFlowAction(FlowActionAbstract $flowAction, string $inPropertyName, string ...$evaluationTypeClassNames): void
    {
        $this->updateParametersOfFlowAction(
            $flowAction,
            static function (array $parameters) use ($inPropertyName, $evaluationTypeClassNames): array {
                $parameters[$inPropertyName] = \implode(',', $evaluationTypeClassNames);

                return $parameters;
            }
        );
    }

    /**
     * @param FlowTransition $flowTransition
     * @param array $flowActionClassNameToPropertyNameMapping
     * @param string[] $evaluationTypeClassNames
     * @throws InvalidJsonFormatException
     * @throws ServiceLifeCycleUpdateException
     */
    public function updateEvaluationTypesOfFlowTransitionActions(
        FlowTransition $flowTransition,
        array $flowActionClassNameToPropertyNameMapping,
        string ...$evaluationTypeClassNames
    ): void {
        foreach ($flowActionClassNameToPropertyNameMapping as $flowActionClassName => $propertyName) {
            $this->withFlowTransitionActionsOfType(
                $flowTransition,
                $flowActionClassName,
                function (FlowActionAbstract $flowAction) use ($propertyName, $evaluationTypeClassNames): void {
                    $this->updateEvaluationTypesOfFlowAction($flowAction, $propertyName, ...$evaluationTypeClassNames);
                }
            );
        }
    }

    /**
     * @param FlowStatus $flowStatus
     * @param array $rights
     * @throws Exception
     */
    public function updateFlowStatusRights(FlowStatus $flowStatus, array $rights): void
    {
        $flowStatus->setRoles($rights);
        $this->flowStatusLifeCycle->update($flowStatus);
    }

    /**
     * @param FlowStatus $flowStatus
     * @param int $role
     * @param array $roleRights
     * @throws InvalidJsonFormatException
     * @throws ServiceLifeCycleUpdateException
     */
    public function updateFlowStatusRightsForRole(FlowStatus $flowStatus, int $role, array $roleRights): void
    {
        $rights = JsonDecoder::decode($flowStatus->getStrRoles());
        $rights[$role] = $roleRights;
        $flowStatus->setRoles($rights);

        $this->flowStatusLifeCycle->update($flowStatus);
    }

    /**
     * @param FlowStatus[] $flowStatuses
     * @throws Exception
     */
    public function setOrderOfFlowStatuses(FlowStatus ...$flowStatuses): void
    {
        $currentPosition = 0;

        foreach ($flowStatuses as $flowStatus) {
            $flowStatus->setIntSort($currentPosition++);
            $this->flowStatusLifeCycle->update($flowStatus);
        }
    }
}
