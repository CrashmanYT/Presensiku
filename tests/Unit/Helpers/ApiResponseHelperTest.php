<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ApiResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiResponseHelperTest extends TestCase
{
    #[Test]
    public function it_creates_a_successful_response()
    {
        $response = ApiResponseHelper::success('Operation successful', ['foo' => 'bar']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('success', $data['status']);
        $this->assertEquals('Operation successful', $data['message']);
        $this->assertEquals(['foo' => 'bar'], $data['data']);
    }

    #[Test]
    public function it_creates_an_error_response()
    {
        $response = ApiResponseHelper::error('An error occurred', ['field' => 'is required'], Response::HTTP_BAD_REQUEST);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('error', $data['status']);
        $this->assertEquals('An error occurred', $data['message']);
        $this->assertEquals(['field' => 'is required'], $data['errors']);
    }

    #[Test]
    public function it_creates_an_info_response()
    {
        $response = ApiResponseHelper::info('Just so you know', ['info' => 'details']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('info', $data['status']);
        $this->assertEquals('Just so you know', $data['message']);
        $this->assertEquals(['info' => 'details'], $data['data']);
    }

    #[Test]
    public function it_creates_a_not_found_response()
    {
        $response = ApiResponseHelper::notFound('Resource not found');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('error', $data['status']);
        $this->assertEquals('Resource not found', $data['message']);
    }

    #[Test]
    public function it_creates_a_validation_error_response()
    {
        $response = ApiResponseHelper::validationError('Validation failed', ['field' => 'is invalid']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('error', $data['status']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertEquals(['field' => 'is invalid'], $data['errors']);
    }
}
