<?php

namespace Tests\Unit\Lib\Validations;

use PHPUnit\Framework\TestCase;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

class ValidationsTest extends TestCase
{
    public function test_not_empty(): void
    {
        $model = new class () extends Model {
            protected static array $columns = ['name'];
        };

        $this->assertFalse(Validations::notEmpty('name', $model));

        $model->name = 'Diego'; // @phpstan-ignore-line
        $this->assertTrue(Validations::notEmpty('name', $model));
    }

    public function test_password_confirmation(): void
    {
        $model = new class () extends Model {
            protected ?string $password = null;
            protected ?string $password_confirmation = null;
        };

        $model->password = '123456'; // @phpstan-ignore-line
        $model->password_confirmation = 'wrong'; // @phpstan-ignore-line

        $this->assertFalse(Validations::passwordConfirmation($model));

        $model->password_confirmation = '123456'; // @phpstan-ignore-line
        $this->assertTrue(Validations::passwordConfirmation($model));
    }

    public function test_password_confirmation_error_is_attached_to_confirmation_field(): void
    {
        $model = new class () extends Model {
            protected ?string $password = 'abcdef';
            protected ?string $password_confirmation = 'outra';
        };

        Validations::passwordConfirmation($model);
        $this->assertEquals('as senhas devem ser idênticas!', $model->errors('password_confirmation'));
        $this->assertNull($model->errors('password'));
    }

    public function test_not_empty_treats_whitespace_as_empty(): void
    {
        $model = $this->modelWith('   ');
        $this->assertFalse(Validations::notEmpty('field', $model));
    }

    public function test_email(): void
    {
        $this->assertFalse(Validations::email('field', $this->modelWith('sem-arroba')));
        $this->assertFalse(Validations::email('field', $this->modelWith('a@')));
        $this->assertTrue(Validations::email('field', $this->modelWith('atleta@arena.com')));
        $this->assertTrue(Validations::email('field', $this->modelWith(null)), 'vazio é tratado pelo notEmpty');
    }

    public function test_min_and_max_length(): void
    {
        $this->assertFalse(Validations::minLength('field', $this->modelWith('12345'), 6));
        $this->assertTrue(Validations::minLength('field', $this->modelWith('123456'), 6));

        $model = $this->modelWith(str_repeat('a', 21));
        $this->assertFalse(Validations::maxLength('field', $model, 20));
        $this->assertEquals('deve ter no máximo 20 caracteres!', $model->errors('field'));
        $this->assertTrue(Validations::maxLength('field', $this->modelWith('ção'), 3), 'conta caracteres, não bytes');
    }

    public function test_format(): void
    {
        $regex = '/^\d{3}\.\d{3}\.\d{3}-\d{2}$/';
        $this->assertFalse(Validations::format('field', $this->modelWith('12345678900'), $regex));
        $this->assertTrue(Validations::format('field', $this->modelWith('123.456.789-00'), $regex));
        $this->assertTrue(Validations::format('field', $this->modelWith(''), $regex));
    }

    public function test_inclusion(): void
    {
        $model = $this->modelWith('superadmin');
        $this->assertFalse(Validations::inclusion('field', $model, ['student', 'manager']));
        $this->assertEquals('possui um valor inválido!', $model->errors('field'));
        $this->assertTrue(Validations::inclusion('field', $this->modelWith('student'), ['student', 'manager']));
    }

    private function modelWith(?string $value): Model
    {
        $model = new class () extends Model {
            protected static array $columns = ['field'];
        };
        $model->field = $value; // @phpstan-ignore-line

        return $model;
    }
}
