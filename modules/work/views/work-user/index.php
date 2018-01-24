<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\base\models\search\WorkUserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '用户列表';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="work-user-index">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            //'id',
            'strUserId',
            'strPhone',
            //'strPass',
            'nickName',
            [
                'attribute' => 'gender',
                'value' => function($model) {
                    return ($model->gender)?'男':'女';
                },
                'headerOptions' => ['width' => '70']
            ],
            [
                'attribute' => 'language',
                'headerOptions' => ['width' => '70']
            ],
            [
                'attribute' => 'city',
                'headerOptions' => ['width' => '70']
            ],
            [
                'attribute' => 'province',
                'headerOptions' => ['width' => '70']
            ],
            [
                'attribute' => 'country',
                'headerOptions' => ['width' => '70']
            ],
            // 'openId',
            [
                'attribute' => 'avatarUrl',
                'label' => '头像',
                'format' => ['image', ['width' => '40', 'height' => '40',]],
                'headerOptions' => ['width' => '50']
            ],
            [
                'attribute' => 'tCreateTime',
                'headerOptions' => ['width' => '180']
            ],
            [
                'attribute' => 'tUpdateTime',
                'headerOptions' => ['width' => '180']
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => '操作',
                'template' => '{view} &nbsp&nbsp;&nbsp;{update}',
                'headerOptions' => ['width' => '70']
            ],
        ],
    ]); ?>
</div>