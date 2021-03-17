<?php
namespace app\models;

use Yii;
class PaginationSuccess extends Success{

    private $pagination;

    /**
     * @inheritdoc
     */
    public function data(){
        if($this->data instanceof \yii\data\DataProviderInterface){
            $this->pagination = $this->data->getPagination();
            $this->pagination->totalCount = $this->data->getTotalCount();
            $this->data = $this->data->getModels();
        }
        $this->setHeaders();
        return parent::data();
    }

    protected function setHeaders(){
        Yii::$app->response->getHeaders()
                ->set('X-Pagination-Total-Count', $this->pagination->totalCount)
                ->set('X-Pagination-Page-Count', $this->pagination->getPageCount())
                ->set('X-Pagination-Current-Page', $this->pagination->getPage() + 1)
                ->set('X-Pagination-Per-Page', $this->pagination->pageSize);

    }
}