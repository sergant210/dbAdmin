<?php

/**
 * Check for updates
 */
class dbAdminCheckUpdateProcessor extends modObjectProcessor {
    public $languageTopics = array('dbadmin');
    public $package = 'dbadmin';
    public $permission = '';

    /**
     * @return array|string
     */
    public function process() {
        $c = $this->modx->newQuery('transport.modTransportPackage');
        $c->select('signature');
        $c->where(array(
            'package_name' => $this->package,
            'provider:>' => 0
        ));
        $c->sortby('version_major','DESC');
        $c->sortby('version_minor','DESC');
        $c->sortby('version_patch','DESC');
        $c->sortby("IF(`release` = '' OR `release` = 'ga' OR `release` = 'pl','z',IF(`release` = 'dev','a',`release`))",'DESC');
        $c->sortby('release_index','DESC');
        $c->limit(1);
        $signature = $this->modx->getValue($c->prepare());

        if (empty($signature)) return $this->failure();

        $response = $this->modx->runProcessor('workspace/packages/update-remote', array('signature'=>$signature));
        if ($response->isError()) {
            return $this->failure();
        }
        return $this->success();
    }
}

return 'dbAdminCheckUpdateProcessor';
