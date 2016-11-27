<?php

class Kint_Object
{
    const ACCESS_NONE = null;
    const ACCESS_PUBLIC = 'public';
    const ACCESS_PROTECTED = 'protected';
    const ACCESS_PRIVATE = 'private';

    const OPERATOR_NONE = null;
    const OPERATOR_ARRAY = '=>';
    const OPERATOR_OBJECT = '->';
    const OPERATOR_STATIC = '::';

    public $name;
    public $type;
    public $static = false;
    public $const = false;
    public $access = self::ACCESS_NONE;
    public $owner_class;
    public $access_path;
    public $operator = self::OPERATOR_NONE;
    public $size = null;
    public $depth = 0;
    public $representations = array();
    public $value_representation = null;
    public $hints = array();

    public function addRepresentation(Kint_Object_Representation $rep, $pos = null)
    {
        if (isset($this->representations[$rep->name])) {
            return false;
        }

        if ($this->value_representation === null) {
            $this->value_representation = $rep;
        }

        if ($pos === null) {
            $this->representations[$rep->name] = $rep;
        } else {
            $this->representations = array_merge(
                array_slice($this->representations, 0, $pos),
                array($rep->name => $rep),
                array_slice($this->representations, $pos)
            );
        }

        return true;
    }

    public function replaceRepresentation(Kint_Object_Representation $rep, $pos = null)
    {
        if ($pos === null) {
            $this->representations[$rep->name] = $rep;
        } else {
            $this->removeRepresentation($rep->name);
            $this->addRepresentation($rep, $pos);
        }
    }

    public function removeRepresentation($name)
    {
        unset($this->representations[$name]);
    }

    public function getRepresentation($name)
    {
        if (isset($this->representations[$name])) {
            return $this->representations[$name];
        } else {
            return false;
        }
    }

    public function getRepresentations()
    {
        return $this->representations;
    }

    public function clearRepresentations()
    {
        $this->representations = array();
    }

    public function getType()
    {
        return $this->type;
    }

    public function getModifiers()
    {
        $out = array(
            $this->getAccess(),
            $this->const ? 'const' : null,
            $this->static ? 'static' : null,
        );

        foreach ($out as $index => $word) {
            if ($word === null) {
                unset($out[$index]);
            }
        }

        return implode(' ', $out);
    }

    public function getAccess()
    {
        switch ($this->access) {
            case self::ACCESS_PRIVATE:
                return 'private';
            case self::ACCESS_PROTECTED:
                return 'protected';
            case self::ACCESS_PUBLIC:
                return 'public';
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getOperator()
    {
        if ($this->operator === self::OPERATOR_ARRAY) {
            return '=>';
        } elseif ($this->operator === self::OPERATOR_OBJECT) {
            return '->';
        } elseif ($this->operator === self::OPERATOR_STATIC) {
            return '::';
        }

        return;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getValueShort()
    {
        if ($rep = $this->value_representation) {
            if ($this->type === 'boolean') {
                return $rep->contents ? 'true' : 'false';
            } elseif ($this->type === 'integer' || $this->type === 'double') {
                return $rep->contents;
            }
        }
    }

    public function getAccessPath()
    {
        return $this->access_path;
    }

    public static function blank($name = null, $access_path = null)
    {
        $o = new self();
        $o->name = $name;
        $o->access_path = $name;
        if ($access_path) {
            $o->access_path = $access_path;
        }

        return $o;
    }

    public function transplant(self $new)
    {
        $new->name = $this->name;
        $new->size = $this->size;
        $new->access_path = $this->access_path;
        $new->access = $this->access;
        $new->static = $this->static;
        $new->const = $this->const;
        $new->type = $this->type;
        $new->depth = $this->depth;
        $new->owner_class = $this->owner_class;
        $new->operator = $this->operator;
        $new->representations = $this->representations;
        $new->value_representation = $this->value_representation;
        $new->hints = array_merge($new->hints, $this->hints);

        return $new;
    }

    public static function isSequential(array $array)
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    public static function sortByAccess(Kint_Object $a, Kint_Object $b)
    {
        static $sorts = array(
            self::ACCESS_PUBLIC => 1,
            self::ACCESS_PROTECTED => 2,
            self::ACCESS_PRIVATE => 3,
            self::ACCESS_NONE => 4,
        );

        return $sorts[$a->access] - $sorts[$b->access];
    }

    public static function sortByName(Kint_Object $a, Kint_Object $b)
    {
        return strcmp($a->name, $b->name);
    }
}
