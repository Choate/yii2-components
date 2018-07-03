<?php

namespace choate\yii2\components\base;


use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\validators\Validator;

class FormModel extends Model
{

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $attributeLabels = [];

    public function __construct()
    {
        parent::__construct([]);
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        if ($this->isDefinedAttribute($name)) {
            return $this->attributes[$name];
        }

        return parent::__get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        if ($this->isDefinedAttribute($name)) {
            $this->attributes[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($name)
    {
        if ($this->isDefinedAttribute($name)) {
            return isset($this->attributes[$name]);
        }

        return parent::__isset($name);
    }

    /**
     * {@inheritdoc}
     */
    public function __unset($name)
    {
        if ($this->isDefinedAttribute($name)) {
            unset($this->attributes[$name]);
        } else {
            parent::__unset($name);
        }
    }

    public function attributes()
    {
        $attributes = parent::attributes();

        return array_merge(array_keys($this->attributes), $attributes);
    }

    public function attributeLabels()
    {
        $attributeLabels = parent::attributeLabels();

        return array_merge($this->attributeLabels, $attributeLabels);
    }

    /**
     * Defines an attribute.
     * @param string $attribute the attribute name
     * @param mixed $value the attribute value
     */
    public function definedAttribute(string $attribute, $value = null)
    {
        if ($this->isDefinedAttribute($attribute)) {
            throw new InvalidArgumentException("\"{$attribute}\" is exists.");
        }
        $this->attributes[$attribute] = $value;
    }

    /**
     * undefined an attribute.
     * @param string $attribute the attribute name
     */
    public function undefinedAttribute(string $attribute)
    {
        if (!$this->isDefinedAttribute($attribute)) {
            throw new InvalidArgumentException("\"{$attribute}\" is exists.");
        }
        unset($this->attributes[$attribute]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isDefinedAttribute(string $name)
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Defined an attribute label.
     *
     * @param string $attribute
     * @param string $label
     */
    public function definedAttributeLabel(string $attribute, string $label)
    {
        if ($this->isDefinedAttribute($attribute)) {
            throw new InvalidArgumentException("\"{$attribute}\" is exists.");
        }
        $this->attributeLabels[$attribute] = $label;
    }

    /**
     * undefined an attribute label.
     * @param string $attribute the attribute name
     */
    public function undefinedAttributeLabel(string $attribute)
    {
        if (!$this->isDefinedAttribute($attribute)) {
            throw new InvalidArgumentException("\"{$attribute}\" is exists.");
        }
        unset($this->attributes[$attribute]);
    }

    /**
     * Adds a validation rule to this model.
     * You can also directly manipulate [[validators]] to add or remove validation rules.
     * This method provides a shortcut.
     * @param string|array $attributes the attribute(s) to be validated by the rule
     * @param mixed $validator the validator for the rule.This can be a built-in validator name,
     * a method name of the model class, an anonymous function, or a validator class name.
     * @param array $options the options (name-value pairs) to be applied to the validator
     * @return $this the model itself
     */
    public function addRule($attributes, $validator, $options = [])
    {
        foreach ((array)$attributes as $attribute) {
            if (!$this->isDefinedAttribute($attribute)) {
                throw new InvalidArgumentException("\"{$attribute}\" is not exists.");
            }
        }
        $validators = $this->getValidators();
        $validators->append(Validator::createValidator($validator, $this, (array)$attributes, $options));

        return $this;
    }

    public function loadDefaultValue() {

    }
}