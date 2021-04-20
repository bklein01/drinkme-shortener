<?php

namespace App\Document;

use App\Service\Config;

class BaseDocument
{
    public static function getOneBy($params, $options = [])
    {
        $config = new Config();
        $manager = new \MongoDB\Driver\Manager($config->getMongodbUrl());
        $objectname = get_called_class();
        $collection = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $objectname));
        $collection = str_replace('app\\document\\', '', $collection);
        $query = new \MongoDB\Driver\Query($params);
        $results = $manager->executeQuery('rate-my-shopper.'.$collection, $query);
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

    public static function getBy($params, $sort = [], $limit = '', $offset = '')
    {
        $config = new Config();
        $manager = new \MongoDB\Driver\Manager($config->getMongodbUrl());
        $objectname = get_called_class();
        $collectionName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $objectname));
        $collectionName = str_replace('app\\document\\', '', $collectionName);
        $collectionName = $collectionName;
        if ((int) $limit > 0) {
            if ($offset == '') {
                $offset = 0;
            }
        } else {
            $limit = 0;
            $offset = 0;
        }
        if (sizeof($sort) > 0) {
            if ($limit > 0) {
                $query = new \MongoDB\Driver\Query($params, ['limit' => $limit, 'skip' => $offset]);
                $results = $manager->executeQuery('rate-my-shopper.'.$collectionName, $query);
                //$results = $mongo->inspecs->$collectionName->find($params)->sort($sort)->limit($limit)->skip($offset);
                $results = iterator_to_array($results);
            } else {
                $query = new \MongoDB\Driver\Query($params);
                $results = $manager->executeQuery('rate-my-shopper.'.$collectionName, $query);
                //$results = $mongo->inspecs->$collectionName->find($params)->sort($sort); //->sort($sort);
                $results = iterator_to_array($results);
            }
        } else {
            if ($limit > 0) {
                $query = new \MongoDB\Driver\Query($params, ['limit' => $limit, 'skip' => $offset]);
                $results = $manager->executeQuery('rate-my-shopper.'.$collectionName, $query);
                //$results = $mongo->inspecs->$collectionName->find($params)->limit($limit)->skip($offset);
                $results = iterator_to_array($results);
            } else {
                $query = new \MongoDB\Driver\Query($params);
                $results = $manager->executeQuery('rate-my-shopper.'.$collectionName, $query);
                //$results = $mongo->inspecs->$collectionName->find($params);
            }
        }
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

    public static function search($params)
    {
        $config = new Config();
        $mongo = new \MongoDB\Driver\Manager($config->getMongodbUrl());
        $objectname = get_called_class();
        $collectionName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $objectname));
        $collectionName = str_replace('app\\document\\', '', $collectionName);
        $results = $mongo->inspecs->$collectionName->aggregate($params);
        $collection = [];
        foreach ($results as $result) {
            $object = $objectname->getOneBy(['_id' => $result['_id']]);
            $collection[] = $object;
        }

        return $collection;
    }

    public function json_encode_objs($item)
    {
        if (!is_array($item) && !is_object($item)) {
            return json_encode($item);
        } else {
            $pieces = [];
            foreach ($item as $k => $v) {
                $pieces[] = "\"$k\":".$this->json_encode_objs($v);
            }

            return '{'.implode(',', $pieces).'}';
        }
    }

    public function create()
    {
        $config = new Config();
        $mongo = new \MongoDB\Driver\Manager($config->getMongodbUrl());
        $objectname = get_called_class();
        $collectionName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $objectname));
        $collectionName = str_replace('app\\document\\', '', $collectionName);
        $data = [];
        $class_vars = get_class_vars(get_class($this));
        foreach ($class_vars as $name => $value) {
            if (is_object($value)) {
                $value = json_decode($this->json_encode_objs($value), true);
            }
            $data[$name] = $this->$name;
        }
        $insRec = new \MongoDB\Driver\BulkWrite();
        $insRec->insert($data);
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
        $result = $mongo->executeBulkWrite('rate-my-shopper.'.$collectionName, $insRec, $writeConcern);
    }

    public function update()
    {
        $config = new Config();
        $mongo = new \MongoDB\Driver\Manager($config->getMongodbUrl());
        $objectname = get_called_class();
        $collectionName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $objectname));
        $collectionName = str_replace('app\\document\\', '', $collectionName);
        $data = [];
        foreach ($this as $name => $value) {
            if (is_object($value)) {
                $value = json_decode($this->json_encode_objs($value), true);
            }
            $data[$name] = $value;
        }
        $mongo->inspecs->$collectionName->update(
            ['_id' => $data['_id']],
            ['$set' => $data]
        );
    }

    public function delete()
    {
        $config = new Config();
        $mongo = new \MongoDB\Driver\Manager($config->getMongodbUrl());
        $objectname = get_called_class();
        $collectionName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $objectname));
        $collectionName = str_replace('app\\document\\', '', $collectionName);
        $data = [];
        foreach ($this as $name => $value) {
            if (is_object($value)) {
                $value = json_decode($this->json_encode_objs($value), true);
            }
            $data[$name] = $value;
        }
        $mongo->inspecs->$collectionName->remove(['_id' => $data['_id']]);
    }
}
