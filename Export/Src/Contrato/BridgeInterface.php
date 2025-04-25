<?php
namespace Modulo\Export\Src\Contrato;

interface BridgeInterface {
    public function registerRoutes(): void;
    public function setContext(string $context): void;
}
