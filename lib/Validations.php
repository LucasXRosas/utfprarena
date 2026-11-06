<?php

namespace Lib;

use Core\Database\Database;

/**
 * Regras de validação reutilizáveis pelos models (chamadas dentro de Model::validates()).
 *
 * Convenção: cada regra registra o erro na chave do próprio atributo ($obj->addError('campo', ...)),
 * para que a view consiga exibir a mensagem embaixo do campo correspondente.
 * As regras de formato/tamanho ignoram valores vazios: a obrigatoriedade é responsabilidade do notEmpty().
 */
class Validations
{
    public static function notEmpty($attribute, $obj)
    {
        if (self::isBlank($obj->$attribute)) {
            $obj->addError($attribute, 'não pode ser vazio!');
            return false;
        }

        return true;
    }

    /**
     * O erro fica em "password_confirmation" para aparecer embaixo do campo de confirmação.
     */
    public static function passwordConfirmation($obj)
    {
        if ($obj->password !== $obj->password_confirmation) {
            $obj->addError('password_confirmation', 'as senhas devem ser idênticas!');
            return false;
        }

        return true;
    }

    public static function email($attribute, $obj)
    {
        $value = $obj->$attribute;
        if (self::isBlank($value)) {
            return true;
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $obj->addError($attribute, 'deve ser um e-mail válido!');
            return false;
        }

        return true;
    }

    public static function minLength($attribute, $obj, int $min)
    {
        $value = $obj->$attribute;
        if (self::isBlank($value)) {
            return true;
        }

        if (mb_strlen((string) $value) < $min) {
            $obj->addError($attribute, "deve ter no mínimo {$min} caracteres!");
            return false;
        }

        return true;
    }

    /**
     * Respeita o tamanho da coluna no banco (evita o erro SQL "Data too long").
     */
    public static function maxLength($attribute, $obj, int $max)
    {
        $value = $obj->$attribute;
        if (self::isBlank($value)) {
            return true;
        }

        if (mb_strlen((string) $value) > $max) {
            $obj->addError($attribute, "deve ter no máximo {$max} caracteres!");
            return false;
        }

        return true;
    }

    public static function format($attribute, $obj, string $regex, string $message = 'está em um formato inválido!')
    {
        $value = $obj->$attribute;
        if (self::isBlank($value)) {
            return true;
        }

        if (preg_match($regex, (string) $value) !== 1) {
            $obj->addError($attribute, $message);
            return false;
        }

        return true;
    }

    /**
     * @param array<int, string> $allowed
     */
    public static function inclusion($attribute, $obj, array $allowed)
    {
        if (!in_array($obj->$attribute, $allowed, true)) {
            $obj->addError($attribute, 'possui um valor inválido!');
            return false;
        }

        return true;
    }

    /**
     * Integridade referencial na camada da aplicação: verifica se o registro
     * apontado pela chave estrangeira existe na tabela relacionada.
     * Ex: Validations::foreignKeyExists('court_id', $image, Court::class)
     */
    public static function foreignKeyExists($attribute, $obj, $relatedClass)
    {
        $value = $obj->$attribute;
        if ($value === null || $value === '') {
            return true; // a obrigatoriedade é responsabilidade do notEmpty
        }

        if (!$relatedClass::exists(['id' => $value])) {
            $obj->addError($attribute, 'registro relacionado inexistente!');
            return false;
        }

        return true;
    }

    public static function uniqueness($fields, $object)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        $table = $object::table();
        $conditions = implode(' AND ', array_map(fn($field) => "{$field} = :{$field}", $fields));

        // Na edição, o próprio registro não conta como duplicado
        if (!$object->newRecord()) {
            $conditions .= ' AND id <> :current_id';
        }

        $sql = <<<SQL
            SELECT id FROM {$table} WHERE {$conditions};
        SQL;

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);

        foreach ($fields as $field) {
            $stmt->bindValue($field, $object->$field);
        }

        if (!$object->newRecord()) {
            $stmt->bindValue('current_id', $object->id);
        }

        $stmt->execute();

        if ($stmt->rowCount() !== 0) {
            foreach ($fields as $field) {
                $object->addError($field, 'já existe um registro com esse dado');
            }
            return false;
        }

        return true;
    }

    private static function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
