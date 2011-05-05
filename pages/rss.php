<?php
/**
* Container for rss feed
*/
class EmolRssPage
{
    /**
    * The slug for the fake post.  This is the URL for your plugin, like:
    * http://site.com/about-me or http://site.com/?page_id=about-me
    * @var string
    */
    var $page_slug = '';

    /**
    * The title for your fake post.
    * @var string
    */
    var $page_title = 'Rss';

    /**
    * Allow pings?
    * @var string
    */
    var $ping_status = 'open';

    /**
    * Function to be executed in eazymatch
    * 
    * @var mixed
    */
    var $emol_function = '';

    /**
    * EazyMatch 3.0 Api
    * 
    * @var mixed
    */
    var $emolApi;


    /**
    * Class constructor
    */
    function EmolRssPage($slug,$function=''){


        $this->page_slug = $slug.'/'.$function;
       
        $this->emol_function = $function;

        //first connect to the api
        $this->emolApi  = eazymatch_connect();

        if( ! $this->emolApi ){
            eazymatch_trow_error();
        }

        try {
            //rss feed
            $wsJob  = $this->emolApi->get('job');
            $jobs   = $wsJob->getPublishedId();
        } catch(SoapFault $e){
            $feed = '';
        }
       
        $items = '';
        $i=0;
        $baseJobUrl = get_bloginfo( 'wpurl').'/'.get_option('emol_job_url');
        foreach($jobs as $job){
            $i++;      
            if($i > 10){
                continue;
            }
            //create trunk
            $trunk = new EazyTrunk();
            
            // create a response array and add all the requests to the trunk
            $fullJob        = &$trunk->request( 'job', 'getBasicPublished', array($job) );
            $texts          = &$trunk->request( 'job', 'getCustomTexts', array($job) );
            $competencies   = &$trunk->request( 'job', 'getPublishedJobCompetence', array($job) );
            
            // execute the trunk request
            $trunk->execute();
            unset($trunk);
            
            //$fullJob                = $wsJob->getBasicPublished($job);
            //$texts                  = $wsJob->getCustomTexts($job);
            //$competencies           = $wsJob->getPublishedJobCompetence($job);
            
            $jobUrl = 'http://'.$baseJobUrl.'/'.$fullJob['id'].'/'.eazymatch_friendly_seo_string($fullJob['name']);
            if($fullJob['startdate'] == null){
                $fullJob['startdate'] = date('Ymd');
            }
            
            $items .= '<item>'.PHP_EOL;
            $items .= '  <guid>'.$jobUrl.'</guid>'.PHP_EOL;
            $items .= '  <title>'.$fullJob['name'].'</title>'.PHP_EOL;
            $items .= '  <pubDate>'.date('r',strtotime($fullJob['datemodified'])).'</pubDate>'.PHP_EOL;
            $items .= '  <link>'.$jobUrl.'</link>'.PHP_EOL;
            $items .= '  <description><![CDATA['.$fullJob['description'].']]></description>'.PHP_EOL;
            
            //now for our custom fields
            $items .= '  <texts>'.PHP_EOL;
            $text='';
            
            //teksten
            foreach( $texts as $val ){
                $items .= '    <'.eazymatch_friendly_seo_string($val['title']).'><![CDATA['.$val['value'].']]></'.eazymatch_friendly_seo_string($val['title']).'>'.PHP_EOL;
            }
            $items .= '  </texts>'.PHP_EOL;
            
            //competencies
           if($competencies[0] !== false){
                $comp = '';
                foreach($competencies as $unit){
                    $comp .= '    <competence id="'.$unit['id'].'" level="'.$unit['level'].'"><![CDATA['.$unit['name'].']]></competence>'.PHP_EOL;
                }
            }
            $items .= '  <competences>'.PHP_EOL.$comp.'</competences>'.PHP_EOL;
            $items .= '</item>'.PHP_EOL;
           
        }
        
        $result = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $result .= '<rss version="2.0">'.PHP_EOL;
        $result .= '  <channel>'.PHP_EOL;
        $result .= '    <title>'.strtoupper(get_option('emol_instance')).'</title>'.PHP_EOL;
        $result .= '    <link>'.get_bloginfo( 'wpurl').'</link>'.PHP_EOL;
        $result .= '    <description>'.'Jobfeed for '.get_option('emol_instance').'</description>'.PHP_EOL;
        $result .= '    <language>'. get_bloginfo('language').'</language>'.PHP_EOL;
        $result .= '    <pubDate>'.date("r").'</pubDate>'.PHP_EOL;
        $result .= $items;
        $result .= '  </channel>'.PHP_EOL;
        $result .= '</rss>'.PHP_EOL;
        
        ob_clean();
        header("content-type:text/xml;charset=utf-8");
       //echo "<pre>";
        print ( $result );
        exit();
    }	
}