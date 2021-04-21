<?php

namespace App\Document;

use App\Service\Config;

class BaseDocument
{
    public static function getOneBy($collection, $params, $options = array())
    {
    	$config = new Config();
        $mongo = new \MongoDB\Driver\Manager($config->getMongodbUrl().'/'.$config->getMongodbDatabase());
        $query = new \MongoDB\Driver\Query($params, $options);
		$results = $mongo->executeQuery($config->getMongodbDatabase().'.'.$collection, $query);
        $results = iterator_to_array($results);
        $objectname = get_called_class();
        $object = new $objectname();
        if (!$results) {
            return null;
        } else {
            $doc = $results[0];
            foreach ($doc as $name => $value) {
                if ($name == '_id') {
                    $name = 'id';
                }
                $methodname = 'set'.ucfirst($name);
                if (method_exists($object, $methodname)) {
                    $object->$methodname($value);
                }
            }
        }

        return $object;
    }

    public static function getBy($collection, $params, $limit = '', $offset = '', $sort = array())
    {
        if ((int) $limit > 0) {
            if ($offset == '') {
                $offset = 0;
            }
        } else {
            $limit = 0;
            $offset = 0;
        }
        $options = array();
        if (sizeof($sort) > 0) {
        	$options['sort'] = $sort;
            if ($limit > 0) {
            	$options['limit'] = $limit;
            	$options['skip'] = $offset;
            }
        } else {
        	$options['sort'] = array();
            if ($limit > 0) {
            	$options['limit'] = $limit;
            	$options['skip'] = $offset;
            }
        }
        $config = new Config();
        $mongo = new \MongoDB\Driver\Manager($config->getMongodbUrl().'/'.$config->getMongodbDatabase());
        $query = new \MongoDB\Driver\Query($params, $options);
		$results = $mongo->executeQuery($config->getMongodbDatabase().'.'.$collection, $query);
        $collection = [];
        foreach ($results as $result) {
            if ($result) {
                $object = new $objectname();
                foreach ($result as $name => $value) {
                    if ($name == '_id') {
                        $name = 'id';
                    }
                    $methodname = 'set'.ucfirst($name);
                    if (method_exists($object, $methodname)) {
                        $object->$methodname($value);
                    }
                }
                $collection[] = $object;
            }
        }

        return $collection;
    }

    public static function search($collection, $params)
    {
    	$config = new Config();
        $mongo = new \MongoDB\Driver\Manager($config->getMongodbUrl().'/'.$config->getMongodbDatabase());
        $query = new \MongoDB\Driver\Query($params, $options);
		$results = $mongo->executeQuery($config->getMongodbDatabase().'.'.$collection, $query);
        return json_decode(json_encode($results->toArray()), true);
    }

    public static function json_encode_objs($item)
    {
        if (!is_array($item) && !is_object($item)) {
            return json_encode($item);
        } else {
            $pieces = array();
            foreach ($item as $k => $v) {
                $pieces[] = "\"$k\":".$this->json_encode_objs($v);
            }

            return '{'.implode(',', $pieces).'}';
        }
    }

    public function create()
    {
        $config = new Config();
        $mongo = new \MongoDB\Driver\Manager($config->getMongodbUrl().'/'.$config->getMongodbDatabase());
        $objectname = get_called_class();
        $collection = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $objectname));
        $collection = str_replace('app\\document\\', '', $collection);
        $classVars = get_class_vars(get_class($this));
        $data = [];
        foreach ($classVars as $name => $value) {
            if (is_object($value)) {
                $value = json_decode($this->json_encode_objs($value), true);
            }
            $data[$name] = $this->$name;
        }
        if ((int) $data['_id'] == 0) {
            $options = array('sort' => array('_id' => -1));
            $params = array('_id' => array('$type' => 'int'));
        	$query = new \MongoDB\Driver\Query($params, $options);
			$results = $mongo->executeQuery($config->getMongodbDatabase().'.'.$collection, $query);
            $count = 0;
            $latest = array();
            foreach ($results as $result) {
            	$latest = json_decode(json_encode($result), true);
            	if ($count == 0) {
            		break;
            	}
            	$count++;
            }
            if (sizeof($latest) > 0) {
                $data['_id'] = (int) $latest['_id'] + 1;
            } else {
            	$data['_id'] = 1;
            }
        }
        $bulk = new \MongoDB\Driver\BulkWrite(['ordered' => true]);
		$bulk->insert($data);
		$writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
	    $result = $mongo->executeBulkWrite($config->getMongodbDatabase().'.'.$collection, $bulk, $writeConcern);
        return $data;
    }

    public function update()
    {
        $config = new Config();
        $mongo = new \MongoDB\Driver\Manager($config->getMongodbUrl().'/'.$config->getMongodbDatabase());
        $objectname = get_called_class();
        $collection = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $objectname));
        $collection = str_replace('app\\document\\', '', $collection);
        $data = [];
        foreach ($this as $name => $value) {
            if (is_object($value)) {
                $value = json_decode($this->json_encode_objs($value), true);
            }
            $data[$name] = $value;
        }
        $bulk = new \MongoDB\Driver\BulkWrite(['ordered' => true]);
        $bulk->update(['_id' => $data['_id']], ['$set' => $data]);
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
	    $result = $mongo->executeBulkWrite($config->getMongodbDatabase().'.'.$collection, $bulk, $writeConcern);
        return $data;
    }

    public function delete()
    {
        $config = new Config();
        $mongo = new \MongoDB\Driver\Manager($config->getMongodbUrl().'/'.$config->getMongodbDatabase());
        $objectname = get_called_class();
        $collection = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $objectname));
        $collection = str_replace('app\\document\\', '', $collection);
        $data = [];
        foreach ($this as $name => $value) {
            if (is_object($value)) {
                $value = json_decode($this->json_encode_objs($value), true);
            }
            $data[$name] = $value;
        }
        $bulk = new \MongoDB\Driver\BulkWrite(['ordered' => true]);
        $bulk->delete(['_id' => $data['_id']]);
	    $result = $mongo->executeBulkWrite($config->getMongodbDatabase().'.'.$collection, $bulk);
        return true;
    }
}
