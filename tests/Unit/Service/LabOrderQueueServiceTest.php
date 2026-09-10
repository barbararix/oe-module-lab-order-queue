<?php

/**
 * Unit tests for LabOrderQueueService.
 * Repository is mocked to isolate business logic validation.
 *
 * @package   OpenEMR
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 */

namespace Juggernaut\OpenEMR\Modules\LabOrderQueue\Tests\Unit\Service;

use Juggernaut\OpenEMR\Modules\LabOrderQueue\Exception\LabOrderQueueException;
use Juggernaut\OpenEMR\Modules\LabOrderQueue\Repository\LabOrderQueueRepository;
use Juggernaut\OpenEMR\Modules\LabOrderQueue\Service\LabOrderQueueService;
use PHPUnit\Framework\TestCase;

class LabOrderQueueServiceTest extends TestCase
{
    private LabOrderQueueRepository $mockRepo;
    private LabOrderQueueService $service;

    protected function setUp(): void
    {
        $this->mockRepo = $this->createMock(LabOrderQueueRepository::class);
        $this->service = new LabOrderQueueService($this->mockRepo);
    }

    // --- validateOrderData tests ---

    /**
     * Test validation catches patient ID error
     * Note: lab_id=0 avoids DB call; lab validation is tested separately
     */
    public function testValidateOrderDataCatchesPatientIdError(): void
    {
        $errors = $this->service->validateOrderData(
            0, // invalid pid
            1,
            ['lab_id' => 0],
            [['procedure_code' => 'CBC', 'procedure_name' => 'Complete Blood Count']]
        );

        $this->assertContains('Patient ID is required', $errors);
    }

    /**
     * Test validation fails with missing patient ID
     */
    public function testValidateOrderDataFailsWithZeroPatientId(): void
    {
        $errors = $this->service->validateOrderData(
            0,
            1,
            ['lab_id' => 0],
            [['procedure_code' => 'CBC', 'procedure_name' => 'CBC']]
        );

        $this->assertNotEmpty($errors);
        $this->assertContains('Patient ID is required', $errors);
    }

    /**
     * Test validation fails with missing provider ID
     */
    public function testValidateOrderDataFailsWithZeroProviderId(): void
    {
        $errors = $this->service->validateOrderData(
            1,
            0,
            ['lab_id' => 0],
            [['procedure_code' => 'CBC', 'procedure_name' => 'CBC']]
        );

        $this->assertNotEmpty($errors);
        $this->assertContains('Provider ID is required', $errors);
    }

    /**
     * Test validation fails with missing lab ID
     */
    public function testValidateOrderDataFailsWithNoLabId(): void
    {
        $errors = $this->service->validateOrderData(
            1,
            1,
            ['lab_id' => 0],
            [['procedure_code' => 'CBC', 'procedure_name' => 'CBC']]
        );

        $this->assertNotEmpty($errors);
        $this->assertContains('Lab provider selection is required', $errors);
    }

    /**
     * Test validation fails with empty procedures array
     */
    public function testValidateOrderDataFailsWithNoProcedures(): void
    {
        $errors = $this->service->validateOrderData(
            1,
            1,
            ['lab_id' => 0],
            []
        );

        $this->assertNotEmpty($errors);
        $this->assertContains('At least one procedure is required', $errors);
    }

    /**
     * Test validation fails when procedure has no code or name
     */
    public function testValidateOrderDataFailsWithEmptyProcedureCodeAndName(): void
    {
        $errors = $this->service->validateOrderData(
            1,
            1,
            ['lab_id' => 0],
            [['procedure_code' => '', 'procedure_name' => '']]
        );

        $this->assertNotEmpty($errors);
        $this->assertContains('Procedure item 1 must have a code or name', $errors);
    }

    /**
     * Test validation accepts procedure with code but no name
     */
    public function testValidateOrderDataAcceptsProcedureWithCodeOnly(): void
    {
        $errors = $this->service->validateOrderData(
            0, // still fails on patient
            1,
            ['lab_id' => 0],
            [['procedure_code' => 'CBC', 'procedure_name' => '']]
        );

        // Should NOT contain the procedure error
        $procedureErrors = array_filter($errors, fn($e) => str_contains($e, 'Procedure item'));
        $this->assertEmpty($procedureErrors);
    }

    /**
     * Test multiple validation errors are collected
     */
    public function testValidateOrderDataCollectsMultipleErrors(): void
    {
        $errors = $this->service->validateOrderData(
            0,
            0,
            ['lab_id' => 0],
            []
        );

        $this->assertGreaterThanOrEqual(4, count($errors));
        $this->assertContains('Patient ID is required', $errors);
        $this->assertContains('Provider ID is required', $errors);
        $this->assertContains('Lab provider selection is required', $errors);
        $this->assertContains('At least one procedure is required', $errors);
    }

    // --- cancelQueuedOrder tests ---

    /**
     * Test cancel throws on invalid queue ID
     */
    public function testCancelQueuedOrderThrowsOnInvalidId(): void
    {
        $this->expectException(LabOrderQueueException::class);
        $this->expectExceptionMessage('Invalid queue ID');

        $this->service->cancelQueuedOrder(0);
    }

    /**
     * Test cancel delegates to repository with valid ID
     */
    public function testCancelQueuedOrderDelegatesToRepository(): void
    {
        $this->mockRepo->expects($this->once())
            ->method('cancelQueuedOrder')
            ->with(42)
            ->willReturn(true);

        $result = $this->service->cancelQueuedOrder(42);
        $this->assertTrue($result);
    }

    // --- attachToEncounter tests ---

    /**
     * Test attach throws on invalid queue ID
     */
    public function testAttachToEncounterThrowsOnInvalidQueueId(): void
    {
        $this->expectException(LabOrderQueueException::class);
        $this->expectExceptionMessage('Invalid queue ID');

        $this->service->attachToEncounter(0, 1, 1);
    }

    /**
     * Test attach throws on invalid encounter ID
     */
    public function testAttachToEncounterThrowsOnInvalidEncounterId(): void
    {
        $this->expectException(LabOrderQueueException::class);
        $this->expectExceptionMessage('Invalid encounter ID');

        $this->service->attachToEncounter(1, 0, 1);
    }

    /**
     * Test attach throws on invalid patient ID
     */
    public function testAttachToEncounterThrowsOnInvalidPid(): void
    {
        $this->expectException(LabOrderQueueException::class);
        $this->expectExceptionMessage('Invalid patient ID');

        $this->service->attachToEncounter(1, 1, 0);
    }

    // --- getQueuedOrderById tests ---

    /**
     * Test getQueuedOrderById enriches with procedures
     */
    public function testGetQueuedOrderByIdEnrichesWithProcedures(): void
    {
        $orderData = [
            'queue_id' => 1,
            'procedure_order_id' => 100,
            'patient_id' => 5,
        ];

        $procedures = [
            ['procedure_code' => 'CBC', 'procedure_name' => 'Complete Blood Count'],
        ];

        $this->mockRepo->expects($this->once())
            ->method('getQueuedOrderById')
            ->with(1)
            ->willReturn($orderData);

        $this->mockRepo->expects($this->once())
            ->method('getOrderProcedures')
            ->with(100)
            ->willReturn($procedures);

        $result = $this->service->getQueuedOrderById(1);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('procedures', $result);
        $this->assertCount(1, $result['procedures']);
        $this->assertEquals('CBC', $result['procedures'][0]['procedure_code']);
    }

    /**
     * Test getQueuedOrderById returns null for non-existent record
     */
    public function testGetQueuedOrderByIdReturnsNullWhenNotFound(): void
    {
        $this->mockRepo->expects($this->once())
            ->method('getQueuedOrderById')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getQueuedOrderById(999);
        $this->assertNull($result);
    }

    // --- passthrough tests ---

    /**
     * Test getQueuedOrdersForPatient delegates to repository
     */
    public function testGetQueuedOrdersForPatientDelegates(): void
    {
        $expected = [['queue_id' => 1], ['queue_id' => 2]];

        $this->mockRepo->expects($this->once())
            ->method('getQueuedOrdersForPatient')
            ->with(5)
            ->willReturn($expected);

        $result = $this->service->getQueuedOrdersForPatient(5);
        $this->assertCount(2, $result);
    }

    /**
     * Test getAllPendingOrders delegates to repository
     */
    public function testGetAllPendingOrdersDelegates(): void
    {
        $expected = [['queue_id' => 1]];

        $this->mockRepo->expects($this->once())
            ->method('getAllPendingOrders')
            ->willReturn($expected);

        $result = $this->service->getAllPendingOrders();
        $this->assertCount(1, $result);
    }

    /**
     * Test countPendingForPatient delegates to repository
     */
    public function testCountPendingForPatientDelegates(): void
    {
        $this->mockRepo->expects($this->once())
            ->method('countPendingForPatient')
            ->with(5)
            ->willReturn(3);

        $result = $this->service->countPendingForPatient(5);
        $this->assertEquals(3, $result);
    }
}
