<?php

require_once __DIR__ . '/../loader.php';

use DK\Question\Controller;
use DK\Question\Input as Input;
use DK\Question\Output as Output;
use DK\Question\Valid as Valid;
use DK\Question\Action as Action;

class Question
{
	
	/**
	 * 列表页面
	 */
	function index()
	{
		$controller = new Controller();
		$controller->addInput(new Input\ListPage());
		$controller->run(new Output\QuestionList());
	}

	/**
	 * 精品问题列表
	 */
	function superList()
	{
		$controller = new Controller();
		$controller->addInput(new Input\ListPage());
		$controller->run(new Output\QuestionSuperList());
	}

	/**
	 * ajax:请求的列表页
	 */
	function getList()
	{
		$controller = new Controller();
		$controller->addInput(new Input\ListPage());
		$controller->run(new Output\QuestionAjaxList());
	}

	/**
	 * ajax:添加问答
	 */
	function addQuestion()
	{
		$controller = new Controller();
		$controller->addInput(new Input\AddQuestion());
		$controller->addValid(new Valid\AddQuestion());
		$controller->addAction(new Action\AddQuestion());
		$controller->run(new Output\AddQuestion());
	}

	/**
	 * 问答详情页
	 */
	function detail()
	{
		$controller = new Controller();
		$controller->addInput(new Input\Question());
		$controller->addValid(new Valid\QuestionExists());
		$controller->run(new Output\QuestionDetail());
	}

	/**
	 * ajax:收藏一个问题
	 */
	function addMark()
	{
		$controller = new Controller();
		$controller->addInput(new Input\Question());
		$controller->addValid(new Valid\QuestionExists());
		$controller->addAction(new Action\AddMark());
		$controller->run(new Output\AddMark());
	}

	/**
	 * ajax:取消收藏
	 */
	function delMark()
	{
		$controller = new Controller();
		$controller->addInput(new Input\Question());
		$controller->addValid(new Valid\QuestionExists());
		$controller->addAction(new Action\DelMark());
		$controller->run(new Output\DelMark());
	}

	/**
	 * ajax:添加精品
	 */
	function addSuper()
	{
		$controller = new Controller();
		$controller->addInput(new Input\Question());
		$controller->addValid(new Valid\QuestionExists());
		$controller->addAction(new Action\AddSuper());
		$controller->run(new Output\AddSuper());
	}

	/**
	 * ajax:添加精品
	 */
	function delSuper()
	{
		$controller = new Controller();
		$controller->addInput(new Input\Question());
		$controller->addValid(new Valid\QuestionExists());
		$controller->addAction(new Action\DelSuper());
		$controller->run(new Output\DelSuper());
	}

	/**
	 * ajax:删除问题
	 */
	function delQuestion()
	{
		$controller = new Controller();
		$controller->addInput(new Input\Question());
		$controller->addValid(new Valid\QuestionExists());
		$controller->addAction(new Action\DelQuestion());
		$controller->run(new Output\DelQuestion());
	}

	/**
	 * ajax得到回答列表
	 */
	function getAnswer()
	{
		$controller = new Controller();
		$controller->addInput(new Input\AnswerPage());
		$controller->run(new Output\AnswerList());
	}

	/**
	 * ajax:回答问题
	 */
	function addAnswer()
	{
		$controller = new Controller();
		$controller->addInput(new Input\AddAnswer());
		$controller->addValid(new Valid\AddAnswer());
		$controller->addAction(new Action\AddAnswer());
		$controller->run(new Output\AddAnswer());
	}

	/**
	 * ajax:对一个回答:顶
	 */
	function praise()
	{
		$controller = new Controller();
		$controller->addInput(new Input\Answer());
		$controller->addValid(new Valid\AnswerExists());
		$controller->addAction(new Action\Praise());
		$controller->run(new Output\Praise());
	}

	/**
	 * ajax:对一个回答:踩
	 */
	function oppose()
	{
		$controller = new Controller();
		$controller->addInput(new Input\Answer());
		$controller->addValid(new Valid\AnswerExists());
		$controller->addAction(new Action\Oppose());
		$controller->run(new Output\Oppose());
	}

	/**
	 * ajax:删除一个回答
	 */
	function delAnswer()
	{
		$controller = new Controller();
		$controller->addInput(new Input\Answer());
		$controller->addValid(new Valid\AnswerExists());
		$controller->addAction(new Action\DelAnswer());
		$controller->run(new Output\DelAnswer());
	}

}
