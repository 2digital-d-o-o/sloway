<?php 

namespace Sloway\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sloway\path;
use Sloway\utils;
use Sloway\config;
use Sloway\url;
use Sloway\arrays;
use Sloway\admin;
use Sloway\mlClass;
use Sloway\dbClass;
use Sloway\genClass;
use Sloway\settings;
use Sloway\acontrol;
use Sloway\task;
use Sloway\cron;

class AdminTasks extends AdminController {
    protected function tasks_model() {
        $res = array(
            array(
                "id" => "id",
                "sort" => true,
                "content" => "#"
            ),
            array(
                "id" => "title", 
                "sort" => true,
                "content" => et("Title")
            ),
            array(
                "id" => "active", 
                "content" => et("Active")
            ),
            array(
                "id" => "schedule", 
                "content" => et("Schedule")
            ),
            array(
                "id" => "command", 
                "content" => et("Command")
            ),
            array(
                "id" => "time_next",
                "content" => et("Time next"),
            ),
            array(
                "id" => "time_last",
                "content" => et("Last time"),
            ),
            array(
                "id" => "modified", 
                "align" => "left",
                "content" => et("Modified"),
            ),
            array(
                "id" => "menu", 
                "align" => "right",
                "fixed" => true,
                "content" => "",
            )
        );
        
        return $res;
    }    

	public function Index() {
		$this->module_path = array(t('Tasks') => '');
		
        $this->model = $this->tasks_model();
		$this->tasks = dbClass::load('task', "*");

        $this->module_content = view("\Sloway\Views\AdminTasks\Index", array(
			"dg_model" => $this->model
		));        
		return $this->admin_view();
	}   
    public function Ajax_Edit($id = 0) {
        if (!$id) {
            $task = dbClass::create("task");
            $task->title = "Task";
        } else
            $task = dbClass::load("task", "@id = $id", 1);
            
        if ($this->input->post('save')) {
            $task->title = $this->input->post("title");
            $task->command = $this->input->post("command");
        
            $sch = array();
            for ($i = 0; $i < 5; $i++) 
                $sch[] = $this->input->post('schedule_' . $i);
            
            $task->schedule = implode(" ", $sch);
            $task->time_next = cron::next($task->schedule);
            $task->save();    
            
            $res["close"] = true;
            $res["result"] = true;
            
            exit(json_encode($res));
        } 
        
        if (!$id) {
            $task = dbClass::create("task");
            $task->title = "Task";
        } else
            $task = dbClass::load("task", "@id = $id", 1);
            
        $schedule = task::schedule($task->schedule);        
        
        $res['title'] = ($id) ? et("Edit task") : et("Create task");
        $res['content'] = view("\Sloway\Views\AdminTasks\Edit", array("task" => $task, "schedule" => $schedule));
        $res['buttons'] = array(
            "save" => array("align" => "left", "title" => t("Save"), "submit" => true, "key" => 13), 
            "cancel"
        );
                
        echo json_encode($res);
    }
    public function Ajax_Handler() {
        if ($delete = $this->input->post("delete")) 
            dbClass::create("task", $delete)->delete();
        
        $sort = $this->input->post("sort", "date");
        $sort_dir = $this->input->post("sort_dir", 1);
        
        $order = "ORDER BY $sort";
        if ($sort_dir == 1)
            $order.= " ASC"; else
            $order.= " DESC";
        
        $tasks = dbClass::load("task", "* $order");
        $result = array(
            "rows" => array(),
            "state" => array(
                "sort" => $sort,
                "sort_dir" => $sort_dir
            ),
        );            
        
        foreach ($tasks as $task) {
			$menu = Admin::IconB("icon-edit.png", false, t("Edit"), "task_edit($task->id)"); 
			$menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "task_delete($task->id)");

            $checked = ($task->active) ? "checked" : "";
            
            $row = array(
                "id" => $task->id,
                "cells" => array(
                    $task->id,
                    "<a onclick='task_edit($task->id)'>$task->title</a>",
                    "<input type='checkbox' $checked onclick='task_status.apply(this)'>",
                    $task->schedule,
                    $task->command,
                    ($task->schedule) ? utils::datetime($task->time_next) : "",
                    ($task->time_last) ? utils::datetime($task->time_last) : "",
                    utils::modified($task->edit_time, $task->edit_user),
                    $menu
                ),
            );
            $result["rows"][] = $row;            
        }        
        
        echo json_encode($result);           
    }
    public function Ajax_Active($id, $st) {
        $r = dbClass::load("task", "@id = $id", 1);
        $r->active = $st;
        $r->next_time = cron::next($r->schedule);//tdCron::getNextOccurrence($r->schedule);
        $r->save();    
    }
}
