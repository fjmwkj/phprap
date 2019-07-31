<?php
namespace app\models\env;

use app\models\Project;
use Yii;
use app\models\Env;

/**
 * This is the model class for form "CreateEnv".
 *
 * @property string $project_id 项目ID
 */
class CreateEnv extends Env
{

    /**
     * 验证规则
     */
    public function rules()
    {
        return [
            [['project_id', 'title', 'name', 'base_url'], 'required'],

            [['title'], 'string', 'max' => 50],
            [['name'], 'string', 'max' => 10],
            [['project_id', 'sort'], 'integer'],

            ['name', 'validateName'],
            ['project_id', 'validateProject'],
        ];
    }

    /**
     * 验证环境名是否唯一
     * @param $attribute
     */
    public function validateName($attribute)
    {

        $query = Env::find();

        $query->andFilterWhere([
            'project_id' => $this->project_id,
            'status' => Env::ACTIVE_STATUS,
            'name'   => $this->name,
        ]);

        if($query->exists()){
            $this->addError($attribute, '抱歉，该环境已存在');
        }

    }

    /**
     * 验证是否有项目操作权限
     * @param $attribute
     */
    public function validateProject($attribute)
    {
        $project = Project::findModel($this->project_id);

        if(!$project->hasRule('env', 'create')){
            $this->addError($attribute, '抱歉，您没有操作权限');
        }
    }

    /**
     * 保存环境
     * @return bool
     */
    public function store()
    {
        if(!$this->validate()){
            return false;
        }

        // 开启事务
        $transaction = Yii::$app->db->beginTransaction();

        // 保存环境
        $env = &$this;

        $env->encode_id = $this->createEncodeId();
        $env->project_id = $this->project_id;
        $env->title = $this->title;
        $env->name  = $this->name;
        $env->base_url = trim($this->base_url, '/');
        $env->status = self::ACTIVE_STATUS;
        $env->sort   = $this->sort?:0;
        $env->creater_id = Yii::$app->user->identity->id;
        $env->created_at = date('Y-m-d H:i:s');

        if(!$env->save()){
            $this->addError($env->getErrorLabel(), $env->getErrorMessage());
            $transaction->rollBack();
            return false;
        }

        // 事务提交
        $transaction->commit();

        return true;

    }

}