<?php

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');
ini_set('memory_limit','-1');
set_time_limit(1000);
require_once('simple_html_dom.php');
$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
function HighlightQuery($query,$line)
{

	for ($i=0;$i<sizeof($query);$i++)
		{

		$line=str_replace($query[$i],"<strong>".$query[$i]."</strong>",$line);
		}

	return $line;
}
function CheckQuery($query,$line)
{

	for ($i=0;$i<sizeof($query);$i++)
	{
		
			if(strpos(strtolower($line),strtolower($query[$i]))!==false)
			{
			return 1;

		}
	}

	return 0;
}
if ($query)
{
  // The Apache Solr Client library should be on the include path
  // which is usually most easily accomplished by placing in the
  // same directory as this script ( . or current directory is a default
  // php include path entry in the php.ini)
  require_once('Apache/Solr/Service.php');
require_once('SpellCorrector.php');
  // create a new solr service instance - host, port, and webapp
  // path (all defaults in this example)
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/newcore');
$temporyQuery=$query;
$q=explode(' ',$query);
 foreach ($q as $terms)
{
$corrector=SpellCorrector::correct($terms);
if (strtolower(trim($corrector))!=strtolower(trim($terms))){
$flag=1;
$temporyQuery= str_replace($terms, $corrector, $temporyQuery);
}


}

  // if magic quotes is enabled then stripslashes will be needed
  if (get_magic_quotes_gpc() == 1)
  {
    $query = stripslashes($query);
  }
	$parameter=[
'q.op' =>'AND'
];
if(array_key_exists("pagerank",$_REQUEST)){
$parameter['sort']="pageRankFile desc";
}
  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)

  try
  {

$results = $solr->search($query, 0, $limit, $parameter);

    
  }
  catch (Exception $e)
  {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}

?>
<html>
  <head>
    <title>PHP Solr Client Example</title>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script src="http://code.jquery.com/jquery-1.10.2.js"></script>
    <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
  </head>
  <body>
    <form  accept-charset="utf-8" method="get">
      <label for="q">Search:</label>
      <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
      <input type="submit"/>
 <input type="reset" value="Reset"/>
<br>
<input type="checkbox" name="pagerank"/ > Using PageRank 
    </form>
<?php
$csv=array();
$file=fopen("mergedMappingFile.csv","r");
while(!feof($file))
{
$line=fgets($file);
$temp=explode(',',$line);
$csv[$temp[0]]=$temp[1];
}
fclose($file);

// display results
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
?>
<?php if ($flag==1)
{?>
<p> Search Instead for :<a href="http://localhost/solr-php-client-master/?q=<?php echo $temporyQuery; ?>"><?php echo $temporyQuery;?></a></p>

<?php }?>   
 <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
    
<?php
  // iterate result documents
  foreach ($results->response->docs as $doc)
  {

?>
  <?php
$id =substr($doc->id, 34);

$title=$doc->title;
if($title==null)
{
$title="NA";
}
$url=$csv[$id];
//$url=$doc->og_url;
$result1=$doc->description;
if($result1==null)
{
$result1="NA";
}
$url1=$url;
$result="";
try{
$htmlURL = file_get_html(trim($url1));





//print_r($q1);

$q=explode(" ",$temporyQuery);
$lines=array();
if (!empty($htmlURL)){
	foreach ($htmlURL->find('p') as $p)
	{
	$lines[]=$p->plaintext;
	}

	for ($i=0;$i<sizeof($lines);$i++)
	{
		for ($j=0;$j<sizeof($q);$j++)
		{




			if(strpos(strtolower($lines[$i]),strtolower($q[$j]))!==false)
			{
				if(strlen($result)<500)
				{

				$result.=HighlightQuery($q,$lines[$i]);
				}
$result.="...";
				break;
			}
			else
			{
			continue;
			}
		}
	}

}
else{
echo "";
}
}
catch (Exception $e)
{
echo "";
}
if ($result=="") 
{

$bit=CheckQuery($q,$result1);
if ($bit==1)
{
$result=HighlightQuery($q,$result1);
$result.="...";
}
else{
$result="NA";
}
}
?>

<p> <a href="<?php echo $url;?>"target="_blank"><?php echo $title;?></a> </p>
<p> <a  style ="color:green" href="<?php echo $url;?>"target="_blank"><?php echo $url;?></a> </p>

<p> <?php echo $result;?> </p>
<br>



 
<?php
  }
?>
    
<?php
}
?>


<script>
        $(function() {
            var prefixLetter = "http://localhost:8983/solr/newcore/suggest?q=";
            var suffixLetter = "&wt=json";
            $("#q").autocomplete({
               

 source : function(request, response) {
               
  

   var prev = $("#q").val().toLowerCase().split(" ").pop(-1);
                    var URL = prefixLetter + prev + suffixLetter;
                   

 $.ajax({
                        url : URL,
                        success : function(data) {
                          

  var prev = $("#q").val().toLowerCase().split(" ").pop(-1);
                            var autocomplete = data.suggest.suggest[prev].autocomplete;
                           

 autocomplete = $.map(autocomplete, function (value, index) {
                              

  var prefix = "";
                                var query = $("#q").val();
                               

 var queries = query.split(" ");
                                if (queries.length > 1) {
                               

     var lastIndex = query.lastIndexOf(" ");
                                    prefix = query.substring(0, lastIndex + 1).toLowerCase();
                                }
                                if (prefix == "" && checkStopWord(value.term)) {
                                    return null;
                                }
                                if (!/^[0-9a-zA-Z]+$/.test(value.term)) {
                                    return null;
                                }
                                return prefix + value.term;
                            });
                           
 response(autocomplete.slice(0, 5));
                        },
                        dataType : 'jsonp',
                        jsonp : 'json.wrf'
                    });
                },
                minLength : 1
            });
        });
        function checkStopWord(word)
        {
            var regex = new RegExp("\\b"+word+"\\b","i");
            return stopWords.search(regex) < 0 ? false : true;
        }
        var stopWords = "a,able,about,above,abst,accordance,according,accordingly,across,act,actually,added,adj,\
        affected,affecting,affects,after,afterwards,again,against,ah,all,almost,alone,along,already,also,although,\
        always,am,among,amongst,an,and,announce,another,any,anybody,anyhow,anymore,anyone,anything,anyway,anyways,\
        anywhere,apparently,approximately,are,aren,arent,arise,around,as,aside,ask,asking,at,auth,available,away,awfully,\
        b,back,be,became,because,become,becomes,becoming,been,before,beforehand,begin,beginning,beginnings,begins,behind,\
        being,believe,below,beside,besides,between,beyond,biol,both,brief,briefly,but,by,c,ca,came,can,cannot,can't,cause,causes,\
        certain,certainly,co,com,come,comes,contain,containing,contains,could,couldnt,d,do,done,\
        each,ed,edu,effect,eight,eighty,either,else,elsewhere,end,ending,enough,\
        especially,et,et-al,etc,even,ever,every,everybody,everyone,everything,everywhere,ex,except,f,far,few,ff,fifth,first,five,fix,\
        followed,following,follows,for,former,formerly,forth,found,four,from,further,furthermore,g,gave,get,gets,getting,give,given,gives,\
        giving,goes,gone,got,gotten,h,had,happens,hardly,has,hasn't,have,haven't,having,he,hed,hence,her,here,hereafter,hereby,herein,\
        heres,hereupon,hers,herself,hes,hi,hid,him,himself,his,hither,home,how,howbeit,however,hundred,i,id,ie,if,i'll,im,immediate,\
        immediately,importance,important,in,inc,indeed,index,information,instead,into,invention,inward,is,isn't,it,itd,it'll,its,itself,\
        i've,j,just,k,keep,keeps,kept,kg,km,know,known,knows,l,largely,last,lately,later,latter,latterly,least,less,lest,let,lets,like,\
        liked,likely,line,little,,look,looking,looks,ltd,m,made,mainly,make,makes,many,may,maybe,me,mean,means,meantime,meanwhile,\
        merely,mg,might,million,miss,ml,more,moreover,most,mostly,much,mug,must,my,myself,n,na,name,namely,nay,nd,near,nearly,\
        necessarily,necessary,need,needs,neither,never,nevertheless,new,next,nine,ninety,no,nobody,non,none,nonetheless,noone,nor,\
        normally,nos,not,noted,nothing,now,nowhere,o,obtain,obtained,obviously,of,off,often,oh,ok,okay,old,omitted,on,once,one,ones,\
        only,onto,or,ord,other,others,otherwise,ought,our,ours,ourselves,out,outside,over,overall,owing,own,p,page,pages,part,\
        particular,particularly,past,per,perhaps,placed,please,plus,poorly,possible,possibly,potentially,pp,predominantly,present,\
        previously,primarily,probably,promptly,proud,provides,put,q,que,quickly,quite,qv,r,ran,rather,re,readily,really,recent,\
        recently,ref,regarding,regardless,regards,related,relatively,research,respectively,resulted,resulting,results,right,run,s,\
        said,same,saw,say,saying,says,sec,section,see,seeing,seem,seemed,seeming,seems,seen,self,selves,sent,seven,several,shall,she,shed,\
        she'll,shes,should,shouldn't,show,showed,shown,showns,shows,significant,significantly,similar,similarly,since,six,slightly,so,\
        some,somebody,somehow,someone,somethan,something,sometime,sometimes,somewhat,somewhere,soon,sorry,specifically,specified,specify,\
        specifying,still,stop,strongly,sub,substantially,successfully,such,sufficiently,suggest,sup,sure,t,take,taken,taking,tell,tends,\
        th,than,thank,thanks,thanx,that,that'll,thats,that've,the,their,theirs,them,themselves,then,thence,there,thereafter,thereby,\
        thered,therefore,therein,there'll,thereof,therere,theres,thereto,thereupon,there've,these,they,theyd,they'll,theyre,they've,\
        think,this,those,thou,though,thoughh,thousand,throug,through,throughout,thru,thus,til,tip,to,together,too,took,toward,towards,\
        tried,tries,truly,try,trying,ts,twice,two,u,un,under,unfortunately,unless,unlike,unlikely,until,unto,up,upon,ups,us,use,used,\
        useful,usefully,usefulness,uses,using,usually,v,value,various,,very,via,viz,vol,vols,vs,w,want,wants,was,wasn't,way,we,wed,\
        welcome,we'll,went,were,weren't,we've,what,whatever,what'll,whats,when,whence,whenever,where,whereafter,whereas,whereby,wherein,\
        wheres,whereupon,wherever,whether,which,while,whim,whither,who,whod,whoever,whole,who'll,whom,whomever,whos,whose,why,widely,\
        willing,wish,with,within,without,won't,words,world,would,wouldn't,www,x,y,yes,yet,you,youd,you'll,your,youre,yours,yourself,\
        yourselves,you've,z,zero";
    </script>
  </body>
</html>
