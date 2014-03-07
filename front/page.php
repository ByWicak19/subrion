<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$preview = false;
	$previewMode = false;
	$name = $iaView->name();

	$iaView->assign('protect', false);

	if (isset($_GET['preview']) && isset($iaCore->requestPath[0]))
	{
		$tname = iaSanitize::sql($iaCore->requestPath[0]);
		if (isset($_SESSION['preview_pages'][$tname]))
		{
			$previewMode = true;
			$newPage = $_SESSION['preview_pages'][$tname];
			$name = $tname;
			if (isset($newPage['titles']))
			{
				if (!is_array($newPage['titles']))
				{
					$pageTitle = $newPage['titles'];
				}
				elseif (isset($newPage['titles'][IA_LANGUAGE]))
				{
					$pageTitle = $newPage['titles'][IA_LANGUAGE];
				}
				$iaView->assign('titles', $pageTitle);
			}
			if (isset($newPage['contents']))
			{
				if (!is_array($newPage['contents']))
				{
					$iaView->assign('content', $newPage['contents']);
				}
				elseif (isset($newPage['contents'][IA_LANGUAGE]))
				{
					$iaView->assign('content', $newPage['contents'][IA_LANGUAGE]);
				}
			}
			if (isset($newPage['passw']) && $newPage['passw'])
			{
				$iaView->assign('page_protect', iaLanguage::get('page_protected', 'Page protected'));
			}
		}
	}

	if (isset($_GET['page_preview']) && isset($iaCore->requestPath[0]))
	{
		$preview = true;
		$name = iaSanitize::sql($iaCore->requestPath[0]);
	}

	$passw = '';
	if (isset($_POST['password']))
	{
		$passw = iaSanitize::sql($_POST['password']);
		$_SESSION['page_passwords'][$name] = $passw;
	}
	elseif (isset($_SESSION['page_passwords'][$name]))
	{
		$passw = $_SESSION['page_passwords'][$name];
	}

	$iaPage = $iaCore->factory('page', iaCore::FRONT);
	$page = $iaPage->getByName($name, $preview ? iaCore::STATUS_DRAFT : iaCore::STATUS_ACTIVE);

	if (!$previewMode && (empty($page) || $iaCore->requestPath))
	{
		return iaView::errorPage(iaView::ERROR_NOT_FOUND);
	}

	// check read permissions
	$page['passw'] = trim($page['passw']);

	if (isset($_POST['password']) && !empty($page['passw']) && $passw != $page['passw'])
	{
		$iaView->setMessages(iaLanguage::get('password_incorrect'), iaView::ERROR_NOT_FOUND);
	}

	if ($page['passw'] != '' && $passw != $page['passw'] && !$previewMode)
	{
		if (!$preview)
		{
			$page = array(
				'meta_description' => $page['meta_description'],
				'meta_keywords' => $page['meta_keywords'],
			);
			$iaView->assign('protect', true);
		}
	}
	if ($preview)
	{
		$iaView->assign('page_protect', iaLanguage::get('page_preview'));
	}

	if (isset($page['unique_tpl']))
	{
		$iaCore->factory('util')->go_to($page['name']);
	}

	$iaView->assign('page', $page);

	$defaultLanguage = $iaCore->get('lang');

	$iaDb->setTable(iaLanguage::getTable());
	$jt_where = "`category` = 'page' AND `key` = 'page_{DATA_REPLACE}_{$name}' AND `code` = '";

	if (!$previewMode)
	{
		$page_title_check = iaLanguage::get('page_title_' . $name, $name);
		$pageTitle = $page_title_check ? $page_title_check : $iaDb->one('`value`', str_replace('{DATA_REPLACE}', 'title', $jt_where) . $defaultLanguage . "'");
		$iaView->title($pageTitle);
	}

	if ($page && !$previewMode)
	{
		$page_content_check = $iaDb->one('`value`', str_replace('{DATA_REPLACE}', 'content', $jt_where) . IA_LANGUAGE . "'");
		$page_content = $page_content_check ? $page_content_check : $iaDb->one('`value`', str_replace('{DATA_REPLACE}', 'content', $jt_where) . $defaultLanguage . "'");

		$iaView->assign('content', $page_content);
	}

	$iaDb->resetTable();

	$iaView->set('description', $page['meta_description']);
	$iaView->set('keywords', $page['meta_keywords']);

	$iaView->display('page');
}