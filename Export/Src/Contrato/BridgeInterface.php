<?php
namespace Modulo\Export\Contrato;

interface BridgeInterface {
    public function registerRoutes(): void;
    public function setContext(string $context): void;
}
