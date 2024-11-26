<?php

namespace app\models;

use Yii;
use yii\web\UploadedFile;

/**
 * This is the model class for table "pelicula".
 *
 * @property int $idpelicula
 * @property string|null $portada
 * @property string|null $titulo
 * @property int|null $anio
 * @property string|null $duracion
 * @property string|null $sinopsis
 * @property int $fk_iddirector
 * 
 * @property ActorHasPelicula[] $actorHasPeliculas
 * @property Actor[] $fkIdactors
 * @property Genero[] $fkIdcategorias
 * @property Director[] $fkIddirector
 * @property PeliculaHasGenero[] $peliculaHasGeneros
 */
class Pelicula extends \yii\db\ActiveRecord
{
    public $imageFile;
    public $actors = [];
    public $genders =[];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pelicula';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['anio', 'fk_iddirector'], 'integer'],
            [['duracion'], 'safe'],
            [['fk_iddirector'], 'required'],
            [['portada', 'sinopsis'], 'string', 'max' => 255],
            [['titulo'], 'string', 'max' => 45],
            [['actors','genders'], 'each', 'rule' => ['integer']],
            //[['fk_iddirector'], 'exist', 'skipOnError' => true, 'targetClass' => Director::class, 'targetAttribute' => ['fk_iddirector']]
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idpelicula' => Yii::t('app', 'Idpelicula'),
            'portada' => Yii::t('app', 'Portada'),
            'titulo' => Yii::t('app', 'Titulo'),
            'anio' => Yii::t('app', 'Año'),
            'duracion' => Yii::t('app', 'Duracion'),
            'sinopsis' => Yii::t('app', 'Sinopsis'),
            'fk_iddirector' => Yii::t('app', 'Director'),
            'actors' => Yii::t('app', 'Actores'), 
            'genders' => Yii::t('app', 'Generos'),
        ];
    }
    public function upload(){
        if($this->validate()){
            if($this->isNewRecord){
                if(!$this->save(false)){
                    return false;
                }
            }
            if($this->imageFile instanceof UploadedFile){
                $filename = $this->idpelicula . '_' . $this->anio . '_movie' . date('Ymd_His') . '.' . $this->imageFile->extension;
                $path = Yii::getAlias('@webroot/portadas/') . $filename;

                if($this->imageFile->saveAs($path)){
                    if($this->portada && $this->portada !== $filename){
                        $this->deletePortada();
                    }

                    $this->portada = $filename;
                }
            }
            return $this->save(false);
        }
        return false;
    }

    public function deletePortada(){
        $path = Yii::getAlias('@webroot/portadas') . $this->portada;
        if(file_exists($path)){
            unlink($path);
        }
    }

    public function afterSave($insert, $changedAttributes){
        parent::afterSave($insert, $changedAttributes);

        if(is_array($this->actors)){
            foreach($this->actors as $actorId){
                $actorHasPelicula = new ActorHasPelicula();
                $actorHasPelicula->fk_idactor = $actorId;
                $actorHasPelicula->fk_idpelicula = $this->idpelicula;
                $actorHasPelicula->save();
            }
        }
    }

    public function beforeDelete(){
        if(!parent::beforeDelete()){
            return false;
        }

        ActorHasPelicula::deleteAll(['fk_idpelicula' => $this->idpelicula]);

        return true;
    }

    /**
     * Gets query for [[ActorHasPeliculas]].
     * 
     * @return \yii\db\ActiveQuery
     */
    public function getActorHasPeliculas()
    {
        return $this->hasMany(ActorHasPelicula::class, ['fk_idpelicula' => 'idpelicula']);
    }

    /**
     * Gets query for [[FkIdactors]].
     * 
     * @return \yii\db\ActiveQuery
     */
    public function getFkIdactors()
    {
        return $this->hasMany(Actor::class, ['idactor' => 'fk_idactor'])->viaTable('actor_has_pelicula', ['fk_idpelicula' => 'idpelicula']);
    }

    /**
     * Gets query for [[fkIdcategorias]].
     * 
     * @return \yii\db\ActiveQuery
     */
    public function getFkIdcategorias()
    {
        return $this->hasMany(Genero::class, ['idcategoria' => 'fk_idcategoria'])->viaTable('pelicula_has_genero', ['fk_idpelicula' => 'idpelicula']);
    }

    /**
     * Gets query for [[Fkiddirector]].
     * 
     * @return \yii\db\ActiveQuery
     */
    public function getFkIddirector()
    {
        return $this->hasOne(Director::class, ['iddirector' => 'fk_iddirector']);
    }

    /**
     * Gets query for[[PeliculaHasGeneros]]
     * 
     * @return \yii\db\ActiveQuery
     */
    public function getPeliculaHasGeneros()
    {
        return $this->hasMany(PeliculaHasGenero::class, ['fk_idpelicula' => 'idpelicula']);
    }

    /**
     * {@inheritdoc}
     * @return PeliculaQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PeliculaQuery(get_called_class());
    }
}
