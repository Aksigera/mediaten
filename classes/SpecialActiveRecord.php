<?php
namespace app\classes;

use app\interfaces\IHaveSpecialFields;
use yii\db\ActiveRecord;
use yii\db\Query;
use Yii;

class SpecialActiveRecord extends ActiveRecord implements IHaveSpecialFields
{
    const MODEL_FIELD = 'model';
    const PROPERTY_NAME_FIELD = 'name';
    const INSTANCE_ID_FIELD = 'model_id';
    const VALUE_FIELD = 'content';


    private $_specialFields = [];

    public function __construct()
    {
        $this->initSpecialFieldsArray();
        parent::__construct();
    }

    public function __set($name, $value)
    {
        if (isset($this->_specialFields[$name]) || array_key_exists($name, $this->_specialFields)) {
            $this->_specialFields[$name] = $value;
            return;
        } else {
            parent::__set($name, $value);
        }
    }

    public function __get($name)
    {
        if (isset($this->_specialFields[$name]) || array_key_exists($name, $this->_specialFields)) {
            return $this->_specialFields[$name];
        }
        return parent::__get($name);
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        if (count($this->_specialFields)) {
            $table = Yii::$app->params['specialTable'];

            foreach ($this->_specialFields as $key => $value) {
                $newCommand = Yii::$app->db->createCommand();

                if ($this->id) {
                    if (isset($value)) {
                        if (self::getIsSpecialPropInDb($key)) {
                            $newCommand->update($table, [
                                self::VALUE_FIELD => $value
                            ], [
                                self::MODEL_FIELD => $this->className(),
                                self::PROPERTY_NAME_FIELD => $key,
                                self::INSTANCE_ID_FIELD => $this->id,
                            ])->execute();
                        } else {
                            $newCommand->insert($table, [
                                self::VALUE_FIELD => $value,
                                self::MODEL_FIELD => $this->className(),
                                self::PROPERTY_NAME_FIELD => $key,
                                self::INSTANCE_ID_FIELD => $this->id,
                            ])->execute();
                        }
                    } elseif (self::getIsSpecialPropInDb($key)) {
                        $newCommand->delete($table, [
                            self::MODEL_FIELD => $this->className(),
                            self::INSTANCE_ID_FIELD => $this->id,
                            self::PROPERTY_NAME_FIELD => $key,
                        ])->execute();

                    }
                } elseif (isset($value)) {
                    $idOfThisInstance = self::getIdOfFollowInstance();
                    $newCommand->insert($table, [
                        self::VALUE_FIELD => $value,
                        self::MODEL_FIELD => $this->className(),
                        self::PROPERTY_NAME_FIELD => $key,
                        self::INSTANCE_ID_FIELD => $idOfThisInstance,
                    ])->execute();
                }
            }
        }

        return parent::save($runValidation, $attributeNames);
    }

    public function addSpecialField($name)
    {
        $this->_specialFields[$name] = null;
        return null;
    }

    private function initSpecialFieldsArray()
    {
        if (isset($this->id)) {
            $query = (new Query())
                ->select(self::PROPERTY_NAME_FIELD, self::VALUE_FIELD)
                ->from(Yii::$app->params['specialTable'])
                ->where([
                    self::MODEL_FIELD => $this::className(),
                    self::INSTANCE_ID_FIELD => $this->id,
                ])
                ->all();
            foreach ($query as $row) {
                $this->_specialFields[$row[self::PROPERTY_NAME_FIELD]] = $row[self::VALUE_FIELD];
            }
        }
    }

    private function getIsSpecialPropInDb($name)
    {
        $table = Yii::$app->params['specialTable'];

        return (new Query())
            ->from($table)
            ->where([
                self::MODEL_FIELD => $this::className(),
                self::INSTANCE_ID_FIELD => $this->id,
                self::PROPERTY_NAME_FIELD => $name,
            ])
            ->one();
    }

    private function getIdOfFollowInstance()
    {
        $preventRecord = $this->find()->orderBy(['id' => SORT_DESC])->one();
        return $preventRecord->id + 1;
    }

}