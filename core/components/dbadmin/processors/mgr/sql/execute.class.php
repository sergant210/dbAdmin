<?php

/**
 * Execute sql query
 */
class dbAdminExecuteQueryProcessor extends modObjectProcessor {
    public $languageTopics = array('dbadmin');
    public $permission = 'sql_query_execute';

    /**
     * @return mixed
     * @throws Exception
     */
    public function process() {
        $query = trim(ltrim($this->getProperty('query'),'\n'));
        $select = preg_match('/^(select|show)/i',$query);
        // Заменяем класс на таблицу
        if (preg_match_all('/\{(\w+)\}/',$query,$match)) {
            for ($i=0; $i<count($match[0]); $i++) {
                $q = $this->modx->newQuery('dbAdminTable');
                $q->where(array(
                    'class:LIKE' => "{$match[1][$i]}",
                ));
                $q->select('name');
                $tableName = $this->modx->getValue($q->prepare());
                $query = str_replace($match[0][$i], $tableName, $query);
            }
        }
        $res = array();
        if (!empty($query)) {
            try {
                if ($stmt = $this->modx->prepare($query)) {
                    $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    if (!$stmt->execute()) {
                        throw new PDOException();
                    }
                    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
                return $this->failure($e->getMessage());
            }
        }
        $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $type = $this->getProperty('outputType');
        switch ($type) {
            case 'print_r':
                $output = print_r($res,1);
                break;
            default:
                $output = var_export($res,1).';';
        }
        return $this->successOutput('', $output, $select, count($res));
    }

    public function successOutput($msg, $data, $select, $number){
        return $this->modx->toJSON(array('success'=>true,'message'=>$msg,'data'=>$data,'select'=>$select, 'number'=>$number));
    }
}
return 'dbAdminExecuteQueryProcessor';