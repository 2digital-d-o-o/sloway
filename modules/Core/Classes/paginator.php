<?php
	namespace Sloway;

    class paginator {
        public $curr = 0;
        public $start = 0;
        public $end = 0;
        public $count = 0;
        public $num_pages = 0;
        public $num_items = 0;
        public $buttons = array();
        public $next = 0;
        public $prev = 0;
        public $perpage = 0;
        public $options = null;
        
        public function __construct($curr, $item_count, $perpage, $ops = null) {       
            if (is_string($ops)) 
                $ops = array("url" => $ops);    
            $this->options = $ops;
            
            if ($curr < 1) $curr = 1;
            
            $this->num_items = $item_count;
            $this->perpage = $perpage;
            
            if ($item_count == 0) {
                $this->curr = 0;
                $this->start = 0;
                $this->end = 0;
                $this->count = 0;
                $this->num_pages = 0;
                $this->buttons = array();
                $this->next = 0;
                $this->prev = 0;
                
                return;
            }
            
            if ($item_count <= $perpage) {
                $this->curr = 1;
                $this->start = 0;
                $this->end = $item_count;
                $this->count = $item_count;
                $this->num_pages = 1;
                $this->buttons = array();
                $this->next = 0;
                $this->prev = 0;
                
                return;
            }

            $this->curr = $curr;
            $this->num_pages = intval($item_count / $perpage);
            if ($item_count % $perpage)
                $this->num_pages++;
                
            if ($curr > $this->num_pages) 
                $curr = $this->num_pages;
                
            $this->start = ($curr-1) * $perpage;
            $this->end = min($curr * $perpage, $item_count);
            $this->count = $this->end - $this->start;
            
            $this->buttons = array();
            for ($i = 1; $i < min($curr - 1 ,3); $i++)             
                $this->buttons[] = $i;
            
            if ($curr > 4) 
                $this->buttons[] = 0; 

            if ($curr > 1) 
                $this->buttons[] = $curr-1;
            
            $this->buttons[] = $curr;

            if ($curr < $this->num_pages) 
                $this->buttons[] = $curr+1;
                
            if ($this->num_pages - $curr > 3)
                $this->buttons[] = 0;

            for ($i = max($curr + 2, $this->num_pages-1); $i <= $this->num_pages; $i++) 
                $this->buttons[] = $i;
            
            $this->prev = ($curr > 1) ? $curr-1 : 0;
            $this->next = ($curr < $this->num_pages) ? $curr + 1 : 0;
        
            return $this;
        }
        public function __toString() {
            return $this->build();    
        }

        public function build() {
            $url = utils::value($this->options, "url", "");
            $class = utils::value($this->options, "class", "");
            $attr = utils::value($this->options, "attr", "");
            $html_next = utils::value($this->options, "html_next", ">");
            $html_prev = utils::value($this->options, "html_prev", "<");
            $html_sep = utils::value($this->options, "html_sep", "...");
            
            $res = "<div class='paginator $class' $attr>";
            if ($this->prev) {
                $href = preg_replace('/%PAGE%/', $this->prev, $url);
                if ($href) $href = "href='$href'";
                
                $res.= "<a $href data-index='$this->prev' class='paginator_page paginator_prev'>$html_prev</a> ";
            }
            foreach ($this->buttons as $index) {
                if ($index == 0)
                    $res.= "<span class='paginator_sep'>$html_sep</span> "; else
                if ($index == $this->curr)
                    $res.= "<span class='paginator_curr'>$index</span> "; 
                else {
                    $href = preg_replace('/%PAGE%/', $index, $url);
                    if ($href) $href = "href='$href'";
                    
                    $res.= "<a $href data-index='$index' class='paginator_page'>$index</a> ";
                }
            }
            
            if ($this->next) {
                $href = preg_replace('/%PAGE%/', $this->next, $url);
                if ($href) $href = "href='$href'";
                
                $res.= "<a $href data-index='$this->next' class='paginator_page paginator_next'>$html_next</a> ";
            }

            $res.= "</div>";
            
            return $res;
        }
    }  
?>
