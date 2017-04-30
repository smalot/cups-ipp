<?php

namespace Smalot\Cups\Connector;

/**
 * Trait OperationIdAware
 *
 * @package Smalot\Cups\Connector
 */
trait OperationIdAware
{

    /**
     * @var int
     */
    protected $operationId;

    /**
     * @param string $type
     *
     * @return int
     */
    public function getOperationId($type = 'current')
    {
        if ($type === 'new') {
            $this->operationId++;
        }

        return $this->operationId;
    }

    /**
     * @param int $operationId
     *
     * @return OperationIdAware
     */
    public function setOperationId($operationId)
    {
        $this->operationId = $operationId;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return mixed
     */
    protected function buildOperationId($type = 'new')
    {
        $operationId = $this->getOperationId($type);
        $metaOperationId = $this->buildInteger($operationId);

        return $metaOperationId;
    }
}
