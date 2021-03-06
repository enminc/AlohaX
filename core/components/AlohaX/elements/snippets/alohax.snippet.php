<?php
/*
 * AholaX
 *
 * Copyright 2011 by Digital Butter <www.butter.com.hk>
 *
 * This file is part of AholaX, a port of Ahola Editor (ahola-editor.com) for MODX Revolution.
 *
 * AholaX is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * AholaX is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * AholaX; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 */
/*

Purpose of this snippet is to inject javascripts that will allow editing on the front end.
For now, only managers will have access unless the below is modified.

*/

//#Auth

if(!$modx->getAuthenticatedUser('mgr')){
	return;
}

//#End Auth

//get options
$assetsPath = $modx->getOption('alohax.assets_path',null,$modx->getOption('assetsPath', $scriptProperties, 'assets/components/AlohaX/'));
$saveOnBlur = (int)$modx->getOption('saveOnBlur', $scriptProperties, 0);
$fields = $modx->getOption('fields', $scriptProperties, 'pagetitle,longtitle,menutitle,introtext,content');
$generateLinkList = $modx->getOption('generateLinkList', $scriptProperties, 1);//this is not very scaleable...

//load this up so we can use it on the front end for storage.
$resource = $modx->getObject('modResource', $modx->resourceIdentifier);
$tvs = $resource->getMany('TemplateVars');
$tvValues = array();
foreach($tvs as $tvId => $tv){
	$tvValues[$tv->get('name')] = $tv->getValue($resource->get('id'));
}

$resourceGraph = array_merge($tvValues, $resource->toArray());

$dirtyFields = $resourceGraph;
foreach($dirtyFields as $fieldName => $val){
	$dirtyFields[$fieldName] = 0;
}

if($fields == '*'){
	//get resource fields
	$fields = $resourceGraph;
	$fields = array_keys($fields);
} else {
	$fields = explode(',', $fields);
}

$resourceJSON = array();
foreach($fields as $field){
	$resourceJSON = array(
		$field => $resourceGraph[$field]
	);
}

$statusBarAssets = array(
	'jquery.statusbar.js'
	,'css/style.css'
);

$alohaAssets = array(
	'aloha.js'
	,'plugins/com.gentics.aloha.plugins.Format/plugin.js'
	,'plugins/com.gentics.aloha.plugins.Table/plugin.js'
	,'plugins/com.gentics.aloha.plugins.List/plugin.js'
	,'plugins/com.gentics.aloha.plugins.Link/plugin.js'
	,'plugins/com.gentics.aloha.plugins.HighlightEditables/plugin.js'
	,'plugins/com.gentics.aloha.plugins.Link/LinkList.js'
	,'plugins/com.gentics.aloha.plugins.Paste/plugin.js'
	,'plugins/com.gentics.aloha.plugins.Paste/wordpastehandler.js'
	,'plugins/at.tapo.aloha.plugins.Image/plugin.js'
	,'alohax.js'
);
//plugin.js is not properly translated

$siteId = $_SESSION["modx.mgr.user.token"];
//used to be $modx->siteId?

$vars = array(
	'assetsPath' => $assetsPath
	,'HTTP_MODAUTH' => $siteId
	,'saveOnBlur' => $saveOnBlur
	,'resourceIdentifier' => $modx->resourceIdentifier
	,'fields' => $fields
	,'resource' => $resourceJSON
	,'dirtyFields' => $dirtyFields
);

$json = $modx->toJSON($vars);
$modx->regClientStartupHTMLBlock(
	'<script type="text/javascript">
		var alohaXSettings = ' . $json . ';
	</script>'
);

foreach($alohaAssets as $script){
	//register all scripts in order.
	$modx->regClientStartupScript($assetsPath . 'aloha/' . $script);
}

foreach($statusBarAssets as $script){
	//register all scripts in order.
	if(end(explode('.', $script)) == 'js'){
		$modx->regClientStartupScript($assetsPath . 'statusbar/' . $script);
	} else {
		$modx->regClientCSS($assetsPath . 'statusbar/' . $script);
	}
	
}

if($generateLinkList){
	$links = array();
	$allResources = $modx->getCollection('modResource', array('published' => 1, 'deleted' => 0));
	foreach($allResources as $resourceId => $resource){
		$links[] = array(
			'name' => strip_tags($resource->get('pagetitle'))
			,'url' => $modx->makeURL($resource->get('id'), '', '', 'full')
			,'type' => 'modx-link'
		);
	}
	$links = $modx->toJSON($links);
	$modx->regClientStartupHTMLBlock(
	'<script type="text/javascript">
		GENTICS.Aloha.Repositories.LinkList.settings.data = ' . $links . ';
	</script>'
)	;
}


