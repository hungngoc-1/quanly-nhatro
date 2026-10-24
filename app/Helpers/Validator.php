<?php

namespace App\Helpers;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        $instance = new self($data);
        $instance->validate($rules, $labels);
        return $instance;
    }

    public function validate(array $rules, array $labels = []): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $ruleList = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $label = $labels[$field] ?? $field;
            $value = $this->data[$field] ?? null;
            $strVal = is_scalar($value) ? trim((string) $value) : '';

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                if ($ruleName === 'required') {
                    if ($value === null || $strVal === '') {
                        $this->addError($field, "{$label} không được để trống.");
                        break;
                    }
                    continue;
                }

                // Nếu trường không bắt buộc và đang trống thì bỏ qua các rule sau
                if ($strVal === '') {
                    continue;
                }

                switch ($ruleName) {
                    case 'numeric':
                        if (!is_numeric($value)) {
                            $this->addError($field, "{$label} phải là một số hợp lệ.");
                        }
                        break;

                    case 'integer':
                        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $this->addError($field, "{$label} phải là số nguyên.");
                        }
                        break;

                    case 'min':
                        $min = (float) ($params[0] ?? 0);
                        if (is_numeric($value) && (float) $value < $min) {
                            $this->addError($field, "{$label} phải lớn hơn hoặc bằng {$min}.");
                        } elseif (!is_numeric($value) && mb_strlen($strVal) < (int) $min) {
                            $this->addError($field, "{$label} phải có ít nhất {$min} ký tự.");
                        }
                        break;

                    case 'max':
                        $max = (float) ($params[0] ?? 255);
                        if (is_numeric($value) && (float) $value > $max) {
                            $this->addError($field, "{$label} không được vượt quá {$max}.");
                        } elseif (!is_numeric($value) && mb_strlen($strVal) > (int) $max) {
                            $this->addError($field, "{$label} không được vượt quá {$max} ký tự.");
                        }
                        break;

                    case 'email':
                        if (!filter_var($strVal, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, "{$label} không đúng định dạng email.");
                        }
                        break;

                    case 'phone':
                        if (!preg_match('/^(0|\+84)[0-9]{8,10}$/', preg_replace('/\s+/', '', $strVal))) {
                            $this->addError($field, "{$label} không đúng định dạng số điện thoại Việt Nam.");
                        }
                        break;

                    case 'cccd':
                        if (!preg_match('/^[0-9]{9,12}$/', preg_replace('/\s+/', '', $strVal))) {
                            $this->addError($field, "{$label} phải bao gồm từ 9 đến 12 chữ số.");
                        }
                        break;

                    case 'date':
                        if (strtotime($strVal) === false) {
                            $this->addError($field, "{$label} không phải là ngày hợp lệ.");
                        }
                        break;

                    case 'in':
                        if (!in_array($strVal, $params, true)) {
                            $this->addError($field, "{$label} có giá trị không hợp lệ.");
                        }
                        break;

                    case 'gte_field':
                        $otherField = $params[0] ?? '';
                        $otherLabel = $labels[$otherField] ?? $otherField;
                        $otherVal = $this->data[$otherField] ?? null;
                        if (is_numeric($value) && is_numeric($otherVal) && (float) $value < (float) $otherVal) {
                            $this->addError($field, "{$label} phải lớn hơn hoặc bằng {$otherLabel}.");
                        }
                        break;

                    case 'after_or_equal_field':
                        $otherField = $params[0] ?? '';
                        $otherLabel = $labels[$otherField] ?? $otherField;
                        $otherVal = (string) ($this->data[$otherField] ?? '');
                        if ($otherVal !== '' && strtotime($strVal) < strtotime($otherVal)) {
                            $this->addError($field, "{$label} phải sau hoặc bằng {$otherLabel}.");
                        }
                        break;
                }
            }
        }

        return empty($this->errors);
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }
}
