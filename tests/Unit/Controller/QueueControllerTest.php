<?php

/**
 * Unit tests for QueueController.
 * Tests the controller's input handling and exception paths.
 * The controller depends on $GLOBALS['kernel'] for Twig, so full dispatch tests
 * require integration testing. These tests focus on validatable logic.
 *
 * @package   OpenEMR
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 */

namespace Juggernaut\OpenEMR\Modules\LabOrderQueue\Tests\Unit\Controller;

use Juggernaut\OpenEMR\Modules\LabOrderQueue\Exception\LabOrderQueueException;
use Juggernaut\OpenEMR\Modules\LabOrderQueue\Service\LabOrderQueueService;
use PHPUnit\Framework\TestCase;

/**
 * Since QueueController instantiates TwigContainer with $GLOBALS['kernel'] in its constructor,
 * and that requires the full OpenEMR environment, we test the service-layer validation that
 * the controller relies on. The controller is a thin layer over the service.
 *
 * Full dispatch/render tests belong in integration tests with the OpenEMR bootstrap.
 */
class QueueControllerTest extends TestCase
{
    /**
     * Test that the service rejects save with invalid data
     * (mimics what controller passes through)
     */
    public function testSaveOrderRejectsInvalidDataViaService(): void
    {
        $mockRepo = $this->createMock(
            \Juggernaut\OpenEMR\Modules\LabOrderQueue\Repository\LabOrderQueueRepository::class
        );
        $service = new LabOrderQueueService($mockRepo);

        $this->expectException(LabOrderQueueException::class);
        $this->expectExceptionMessage('Validation failed');

        // Simulate what controller would pass with empty form
        $service->createQueuedOrder(0, 0, ['lab_id' => 0], []);
    }

    /**
     * Test that attach rejects zero queue ID
     */
    public function testAttachRejectsZeroQueueId(): void
    {
        $mockRepo = $this->createMock(
            \Juggernaut\OpenEMR\Modules\LabOrderQueue\Repository\LabOrderQueueRepository::class
        );
        $service = new LabOrderQueueService($mockRepo);

        $this->expectException(LabOrderQueueException::class);
        $this->expectExceptionMessage('Invalid queue ID');

        $service->attachToEncounter(0, 1, 1);
    }

    /**
     * Test that attach rejects zero encounter ID
     */
    public function testAttachRejectsZeroEncounterId(): void
    {
        $mockRepo = $this->createMock(
            \Juggernaut\OpenEMR\Modules\LabOrderQueue\Repository\LabOrderQueueRepository::class
        );
        $service = new LabOrderQueueService($mockRepo);

        $this->expectException(LabOrderQueueException::class);
        $this->expectExceptionMessage('Invalid encounter ID');

        $service->attachToEncounter(1, 0, 1);
    }

    /**
     * Test that cancel rejects zero queue ID
     */
    public function testCancelRejectsZeroQueueId(): void
    {
        $mockRepo = $this->createMock(
            \Juggernaut\OpenEMR\Modules\LabOrderQueue\Repository\LabOrderQueueRepository::class
        );
        $service = new LabOrderQueueService($mockRepo);

        $this->expectException(LabOrderQueueException::class);
        $this->expectExceptionMessage('Invalid queue ID');

        $service->cancelQueuedOrder(0);
    }

    /**
     * Test that cancel delegates to repo for valid ID
     */
    public function testCancelDelegatesToRepoForValidId(): void
    {
        $mockRepo = $this->createMock(
            \Juggernaut\OpenEMR\Modules\LabOrderQueue\Repository\LabOrderQueueRepository::class
        );
        $mockRepo->expects($this->once())
            ->method('cancelQueuedOrder')
            ->with(5)
            ->willReturn(true);

        $service = new LabOrderQueueService($mockRepo);
        $result = $service->cancelQueuedOrder(5);
        $this->assertTrue($result);
    }

    /**
     * Test POST data extraction pattern for procedures
     * (mimics what the controller does to build procedures array)
     */
    public function testProcedureArrayBuildingFromPostArrays(): void
    {
        // Simulate POST data as the controller receives it
        $procCodes = ['CBC', 'BMP'];
        $procNames = ['Complete Blood Count', 'Basic Metabolic Panel'];
        $procDiagnoses = ['Z00.00', ''];
        $procTitles = ['procedure', 'procedure'];
        $procTransport = ['', 'frozen'];
        $procTypes = ['laboratory_test', 'laboratory_test'];

        $procedures = [];
        foreach ($procCodes as $i => $code) {
            $procedures[] = [
                'procedure_code' => $code,
                'procedure_name' => $procNames[$i] ?? '',
                'diagnoses' => $procDiagnoses[$i] ?? '',
                'procedure_order_title' => $procTitles[$i] ?? '',
                'transport' => $procTransport[$i] ?? '',
                'procedure_type' => $procTypes[$i] ?? 'laboratory_test',
            ];
        }

        $this->assertCount(2, $procedures);
        $this->assertEquals('CBC', $procedures[0]['procedure_code']);
        $this->assertEquals('Basic Metabolic Panel', $procedures[1]['procedure_name']);
        $this->assertEquals('Z00.00', $procedures[0]['diagnoses']);
        $this->assertEquals('frozen', $procedures[1]['transport']);
    }
}
