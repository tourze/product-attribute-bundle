<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\ProductAttributeBundle\Exception\AttributeException;

/**
 * @internal
 */
#[CoversClass(AttributeException::class)]
class AttributeExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $message = '属性操作异常';
        $code = 1001;
        $previous = new \Exception('前置异常');

        $exception = new AttributeException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($previous, $exception->getPrevious());
    }

    public function testExceptionWithDefaultParameters(): void
    {
        $exception = new AttributeException();

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithOnlyMessage(): void
    {
        $message = '属性不存在';
        $exception = new AttributeException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = '属性值非法';
        $code = 2001;
        $exception = new AttributeException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new AttributeException();

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionThrown(): void
    {
        $this->expectException(AttributeException::class);
        $this->expectExceptionMessage('测试异常');
        $this->expectExceptionCode(3001);

        throw new AttributeException('测试异常', 3001);
    }

    public function testExceptionChaining(): void
    {
        $originalException = new \InvalidArgumentException('原始异常');
        $attributeException = new AttributeException('属性异常', 0, $originalException);

        $this->assertEquals($originalException, $attributeException->getPrevious());
        $previous = $attributeException->getPrevious();
        $this->assertInstanceOf(\InvalidArgumentException::class, $previous);
        $this->assertEquals('InvalidArgumentException', get_class($previous));
    }

    public function testExceptionToString(): void
    {
        $exception = new AttributeException('测试异常信息', 4001);
        $exceptionString = (string) $exception;

        $this->assertStringContainsString('AttributeException', $exceptionString);
        $this->assertStringContainsString('测试异常信息', $exceptionString);

        $this->assertEquals(4001, $exception->getCode());
    }

    public function testExceptionFile(): void
    {
        $exception = new AttributeException('文件测试');

        $this->assertEquals(__FILE__, $exception->getFile());
        $this->assertIsInt($exception->getLine());
        $this->assertGreaterThan(0, $exception->getLine());
    }

    public function testExceptionTrace(): void
    {
        $exception = new AttributeException('堆栈跟踪测试');
        $trace = $exception->getTrace();

        $this->assertIsArray($trace);
        $this->assertNotEmpty($trace);

        $traceString = $exception->getTraceAsString();
        $this->assertIsString($traceString);
        $this->assertNotEmpty($traceString);
    }

    public function testStaticFactoryMethods(): void
    {
        // 测试静态工厂方法（如果存在）
        $message = '静态工厂方法测试';

        // 如果 AttributeException 没有静态方法，这个测试就简单验证基本功能
        $exception = new AttributeException($message);
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionSerialization(): void
    {
        $exception = new AttributeException('序列化测试', 5001);

        // 测试序列化和反序列化
        $serialized = serialize($exception);
        $this->assertIsString($serialized);

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(AttributeException::class, $unserialized);
        $this->assertEquals($exception->getMessage(), $unserialized->getMessage());
        $this->assertEquals($exception->getCode(), $unserialized->getCode());
    }
}
