<?php

class Options {
    private $defaults = [];
    private $values = [];

    public function __construct($optionSet, $valueSet = null) {
        foreach($optionSet as $name => $value) {
            $this->defaults[$name] = $value;
            $this->values[$name] = $value;
        }
        if ($valueSet) {
            $this->setValues();
        }
    }

    public function setValues($valueSet) {
        $this->values = [];
        $badNames = [];
        foreach($valueSet as $n => $v) {
            if (!array_key_exists($n, $this->defaults)) {
                array_push($badNames, $n);
            }
        }
        if (count($badNames) > 0) {
            $msg = sprintf("Unknown option(s) '%s'. Available are: '%s'.", implode("', '", $badNames), implode("', '",array_keys($this->defaults)));
            throw(new Exception($msg, 500));
        }

        foreach($this->defaults as $name => $defaultValue) {
            if (array_key_exists($name, $valueSet)) {
                $this->values[$name] = $valueSet[$name];
            } else {
                $this->values[$name] = $this->defaults[$name];
            }
        }
    }

    public function valueOf($name) {
        if (!array_key_exists($name, $this->values)) {
            $msg = sprintf("Unknown option '%s'. Available are: '%s'.", $name, implode("', '",array_keys($this->defaults)));
            throw(new Exception($msg, 500));
        }
        return $this->values[$name];
    }
}

?>