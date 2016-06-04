<?php
//Checking if WP is running or if this is a direct call..
defined('ABSPATH') or die();

add_filter('the_content', 'ziggeo_content_filter');
add_filter('comment_text', 'ziggeo_content_filter');
add_filter('the_excerpt', 'ziggeo_content_filter');
add_filter('thesis_comment_text', 'ziggeo_content_filter');

// -- Default values to use: 'ziggeo-width=320 ziggeo-height=240'
function ziggeo_content_replace($matches) {
    $options = get_option('ziggeo_video');
    $default = 'ziggeo-width=320 ziggeo-height=240';
    $video_token = trim($matches[1]);
    $tagname = "ziggeo";
    if (@$video_token) { //so if there is video token it is player, while if it is not set, it is not.. This means that it was not as easy to set up re-recorder.
        if (isset($options, $options["beta"])) {
            $tagname = "ziggeoplayer";
        }
        $config = isset($options, $options["player_config"]) ? $options["player_config"] : $default; 
        
        return "<" . $tagname . " ba-theme='modern' " . $config . " ziggeo-video='" . $video_token . "'></" . $tagname . ">";
    } else {
        $config = isset($options, $options["recorder_config"]) ? $options["recorder_config"] : $default;
        try {
            $current_user = wp_get_current_user();
            $config .= ' ziggeo-tags="' . $current_user->user_login . '"';
        } catch (Exception $e) {}

        return "<" . $tagname . " " . $config . "'></" . $tagname . ">"; //This seems to hold ' too many..
    }
}

//This works like shortcode does, allowing us to capture the codes through various filters and parse them as needed.
function ziggeo_content_replace_templates($matches)
{
    $ret = '';
    $beta_params = array('stretch' => true, 'theme' => true, '_wpbeta_' => true); //the last one just to state if the template is beta or not..
    $type = null; //the type of tag that we are using..

    //So that we can fill out the tags as we did before.
    $current_user = wp_get_current_user();

    //Lets check what we are filtering through at this time..
    $filter = current_filter();

    //We will need to check few options from the DB..
    $options = get_option('ziggeo_video');

    //Lets add a tag to the video, that the same is not only a wordpress video, but that it is also one made in comments ;)
    if( $filter === 'comment_text' || $filter === 'thesis_comment_text' ) {
        $locationTag = 'comment';

        //1. Test if there is a specific template to use when playing comments
        //2. if so get its parameters and combine them with video token and pass further.
        $tmp = '[ziggeo ';

        if( isset($options['comments_player_template']) && !empty($options['comments_player_template']) ) {
            $commentTemplateID = $options['comments_player_template'];

            $tmp .= ziggeo_template_params($commentTemplateID);
        }

        $index = stripos($matches[0], ' video=');
        $tmp .=  substr($matches[0], $index, stripos($matches[0], ' ', $index+1) );

        $matches[0] = $tmp;
        $matches[1] = 'ignore';
    }
    //For now it is else, in future, we can expend this to include other filters.
    else {
        $locationTag = 'post';
    }

    //We are listing all of the required parameters for that specific tag. All of the player tags will however have ziggeo as their base.
    $presets = array (
        'ziggeo' => array (
                        'width' => 320,
                        'height' => 240
                    ),
        'ziggeoplayer' => array (
                        'video' => '', //requires token to play video. If it is not added, we will pass empty token, so that they are shown the player error and know that they need to fix it.
                        'width' => 320,
                        'height' => 240
                    ),
        'ziggeorecorder' => array (
                        'tags' => array ('wordpress', $current_user->user_login, $locationTag ),
                        'width' => 320,
                        'height' => 240
                    ), //tags are pre-set
        'ziggeorerecorder' => array (
                        'video' => '', //requires token to play video. If it is not added, we will pass empty token, so that they are shown the player error and know that they need to fix it.
                        'tags' => array ('wordpress', $current_user->user_login, $locationTag ),
                        'width' => 320,
                        'height' => 240
                    ),
        'ziggeouploader' => array (
                        'perms' => array ('allowupload', 'forbidrecord'),
                        'width' => 320,
                        'height' => 240
                    ),
        //These are to be done later...
        'ziggeovideowall' => array (), //!uses different parameters
        'ziggeoform' => array () //!uses different parameters
    );

    //The new templates called the old way..[ziggeoplayer]TOKEN[/ziggeoplayer]
    if(isset($matches, $matches[3]))
    {
        $full = '[ziggeo ';
        $params = '';

        if($matches[3] === 'rerecorder') {
            $full = '[ziggeorerecorder ';

            //Since we know that it is re-recorder, lets check if we 
            if( ( $start = stripos($matches[2], 'modes') ) > -1) {
                if(stripos($matches[2], 'rerecorder', $start) === false) {
                    //we have modes set, but they do not have rerecorder as one of the modes.. since rerecorder tag is used, it should override the same
                    $matches[2] = str_replace('modes="', ' modes="rerecorder,', $matches[2]);
                }
            }
            //we did not have mode set, so lets set it up by adding at the start
            else {
                $full .= ' modes="rerecorder" ';
            }
        }

        $tmp = stripos($matches[0], ' ');
        $tmp = substr($matches[0], $tmp, stripos($matches[0], ']') - $tmp+1 );

        //Did we add the template through TinyMCE? If so we will have a string placeholder in there..
        if(stripos($matches[0], 'YOUR_VIDEO_TOKEN') > -1) {
            $params = str_replace('YOUR_VIDEO_TOKEN', $matches[2], $tmp);
        }
        //OK, so we have someone that writes their own templates and specific term provided by TinyMCE was not used..
        else {
            //Do we have video parameter mentioned at all? If not, we will just add it to the list, otherwise, lets do some search and replace instead.
            if( ($start = stripos($matches[0], 'video=') ) > -1) {

                $params = str_replace('video=', '', $matches[1]);
                //$params = str_replace($matches[0], 'video=', '');
                $params .= ' video="' . $matches[2] . '" ';

            }
            //we just addd video parameter to the existing list of parameters
            else {
                $params = ' video="' . $matches[2] . '" ';
            }
        }
        $full .= $params;

        return ziggeo_content_replace_templates( array($full, $params) );
    }

    //This should be active for new templates only
    if(isset($matches, $matches[1]) )
    {
        $savedVideo = false;

        //Quick check to see if we have video= in there or not..
        //This would happen if we use tinyMCE to add template
        if( ($ts = stripos($matches[0], 'video=')) > -1 ) {
            //[ziggeo comments player video='bb9c5916d80277f7edba2d088c8c16a3']
            $savedVideo = substr($matches[0], $ts );
            $savedVideo = str_replace( ']', '', $savedVideo );
            $matches[0] = str_replace( $savedVideo, '', $matches[0]);
        }

        //These are parameters sent to us through the [ziggeo] shortcode. It can be a raw setup like: " width=320 height=240 limit=4" or template ID/name
        $parameters = trim($matches[1]);
        $fullMatch = $matches[0];

        //It could be an empty list.. if it is, then we should apply defaults to the same and just send it up..
        if($parameters === "") {
            return '<ziggeo ziggeo-width=320 ziggeo-height=240 ziggeo-tags="' . implode(',', $presets['ziggeorecorder']['tags']) . '"></ziggeo>';
        }

        //There is something for us to do, so lets determine what is our starting tag at this stage since later we will use it if it is template and if it is not
        else {
            $tag = '';

            //Is it base?
            if( stripos($fullMatch, '[ziggeo ') > -1 ) {
                $parameters = substr($fullMatch, 8, -1);
                $tag = 'ziggeo';
            }
            //Is it video player
            if( stripos($fullMatch, 'ziggeoplayer') > -1 ) {
                $parameters = substr($fullMatch, 13, -1);
                $tag = 'ziggeoplayer';
            }
            //is it recorder
            elseif( stripos($fullMatch, 'ziggeorecorder') > -1 ) {
                $parameters = substr($fullMatch, 15, -1);

                $tag = 'ziggeorecorder';
            }
            //is it re-recorder
            elseif( stripos($fullMatch, 'ziggeorerecorder') > -1 ) {
                $parameters = substr($fullMatch, 17, -1);

                $tag = 'ziggeorerecorder';
            }
            //is it uploader
            elseif( stripos($fullMatch, 'ziggeouploader') > -1 ) {
                $parameters = substr($fullMatch, 15, -1);

                $tag = 'ziggeouploader';
            }
            //is it videowall
            elseif( stripos($fullMatch, 'ziggeovideowall') > -1 ) {
                $parameters = substr($fullMatch, 16, -1);

                $tag = 'ziggeovideowall';
            }
            //is it form
            elseif( stripos($fullMatch, 'ziggeoform') > -1 ) {
                $parameters = substr($fullMatch, 11, -1);

                $tag = 'ziggeoform';
            }

            //When the call is not done right, we might not get it captured by [ziggeo test at the top, so we should check it again..
            if($tag === '') {
                if( stripos($fullMatch, '[ziggeo') > -1 ) {
                    $parameters = substr($fullMatch, 8, -1);
                    $tag = 'ziggeo';
                }
            }
        }

        //Lets determine if it is ID/name of a template and call it
        if( $template = ziggeo_template_exists( $parameters ) ) {

            //Lets check if we sent the video along with template name, and if we did, lets give it back its video.
            if($savedVideo) {
                
                if( stripos($template, ' video=') ) {
                    $template = str_ireplace( array('video=""', "video=''"), ' ' . $savedVideo . ' ', $template);
                }
                else {
                    $template = str_replace( ']', ' ' . $savedVideo . ']', $template);
                }
            }

            //At this time the parameters holds the template ID not parameters and temaplte is having the the template loaded with tags and everything..
            return ziggeo_content_replace_templates(array($template, $template));
        }
        //if it is not a template name, it is likely parameters list, so just post it 'as is'..
        else {
            //This is the actual processing ;)

            //Lets check if we sent the video along with template name, and if we did, lets give it back its video.
            if($savedVideo) {
                $parameters .= ' ' . $savedVideo;
            }

            $template = ziggeo_parameter_processing($presets[$tag], $parameters);

            $tagName = 'ziggeo';

            //Check if there are any beta tags in there..
            foreach($beta_params as $param => $value) {
                if( stripos($template, $param) > -1 ) {
                    $tagName = 'ziggeoplayer';

                    //Since this is only plugin specific beta option - per template, so we need to remove it so that it is not passed to Ziggeo API
                    if($param === '_wpbeta_')    { $template = str_replace('_wpbeta_', '', $template); }
                }
            }
            
            // --- VIDEO WALL ---
            // ------------------

            //To check if this is a call for video wall or form since we serve them differently
            if($tag === 'ziggeovideowall') {

                //Since there could be several walls on the same page, it would be best to create some random id that will help distinguish the x from y
                $wallID = 'ziggeo_video_wall' . str_replace(array(' ', '.'), '', microtime()); ///ziggeo_video_wall0363734001464901560
var_dump($wallID);
                $ret = '<div id="' . $wallID . '" class="ziggeo_videoWall" ';

                $wall = ziggeo_wall_parameter_values($template);

                //To set up the wall inline style
                $wallStyles = '';

                //It would not be possible to use pixels and percentages in the same time, so to avoid bad HTML and CSS code percentages will rule the pixels when both are set
                if(!isset($wall['scalable_width']) && isset($wall['fixed_width'])) {
                    $wallStyles .= 'width:' . trim($wall['fixed_width']) . 'px;';
                }

                if(!isset($wall['scalable_height']) && isset($wall['fixed_height'])) {
                    $wallStyles .= 'height:' . trim($wall['fixed_height']) . 'px;';
                }
                
                if(isset($wall['scalable_width'])) {
                    $wallStyles .= 'width:' . trim($wall['scalable_width']) . '%;';
                }

                if(isset($wall['scalable_height'])) {
                    $wallStyles .= 'height:' . trim($wall['scalable_height']) . '%;';
                }
                
                if(isset($wall['show'])) {
                    $wallStyles .= 'display:block;';
                }
                else {
                    $wallStyles .= 'display:none;';
                }

                //adding inline style
                $ret .= 'style="' . $wallStyles . '"';
                $ret .= '>'; //closing video wall starting element

                //Does wall have the title parameter set up?
                if( isset($wall['title']) ) {
                    //Lets get the title then
                    $wall['title'] = '<div class="ziggeo_wall_title">' . $wall['title'] . '</div>';
                }
                else {
                    //will be needed because of CSS
                    $wall['title'] = '<div class="ziggeo_wall_title" style="display:none"></div>';
                }

                //show_pages is default, so if slide_wall is set, it will be used over show_pages
                if(!isset($wall['slide_wall'])) {
                    $wall['show_pages'] = true; //regardless if it is mentioned or not since this is default
                    $wall['slide_wall'] = false; //regardless if it is mentioned or not since this is default

                    //videos per page
                    if(!isset($wall['videos_per_page'])) { $wall['videos_per_page'] = 2; }
                }
                else {
                    $wall['show_pages'] = false; //since the slide_wall was set, we turn off the show pages.
                    $wall['slide_wall'] = true; //since the slide_wall was set, we turn off the show pages.

                    if(!isset($wall['videos_per_page'])) { $wall['videos_per_page'] = 1; }
                }

                //getting the defaults:
                
                //video width
                if(!isset($wall['video_width'])) { $wall['video_width'] = 480; }

                //video height
                if(!isset($wall['video_height'])) { $wall['video_height'] = 360; }

                //lets set the post ID since we will need to reference it as tag
                $wall['postID'] = get_the_ID();
$wall['postID'] = 129;
                //what kind of videos to show - defaults to approved ones
                if(!isset($wall['show_videos'])) { $wall['show_videos'] = 'approved'; }



                //To handle search and everything, we will use JS, otherwise we would need to include SDK (which would be OK, however it would also cause a lot more code to be present and would be hard to update if needed)
                //to use it through client side, we will now build JS templates which will be outputted to the page.


                //links to the background image, since CSS can not be hard coded (and make it work everywhere)
                ?>
                <style type="text/css">
                    .ziggeo_videowall_slide_previous {
                        background-image: url("<?php echo ZIGGEO_ROOT_URL . 'images/arrow-previous.png'; ?>");
                    }
                    .ziggeo_videowall_slide_next {
                        background-image: url("<?php echo ZIGGEO_ROOT_URL . 'images/arrow-next.png'; ?>");
                    }
                </style>
                <script type="text/javascript">
                    if(typeof ZiggeoWall === 'undefined') {
                        var ZiggeoWall = [];
                    }

                    ZiggeoWall['<?php echo $wallID; ?>'] = {
                        videos: {
                            width: <?php echo $wall['video_width']; ?>,
                            height: <?php echo $wall['video_height']; ?>
                        },
                        indexing: {
                            perPage: <?php echo $wall['videos_per_page']; ?>,
                            status: '<?php echo $wall['show_videos']; ?>', //good to note that we should search using tags, by default, this is to fine tune the results that are matching the post ID tag.
                            showPages: <?php echo ($wall['show_pages'] ? 'true' : 'false'); ?>,
                            slideWall: <?php echo ($wall['slide_wall'] ? 'true' : 'false'); ?>
                        },
                        title: '<?php echo $wall['title']; ?>'
                    };

                    //in case there are multiple walls on the same page, we want to be sure not to cause issues. This should catch it and not declare the function again.                    
                    if(typeof ziggeoShowVideoWall !== 'function') {                        
                        //show video wall based on its ID
                        function ziggeoShowVideoWall(id) {
                            //reference to wall
                            var wall = document.getElementById(id);

                            //lets check if wall is existing or not. If not, we break out and report it.
                            if(!wall) {
                                console.log('Exiting function. Specified wall is not present');
                                return false;
                            }

                            if(!ZiggeoWall[id]) {
                                console.log('Wall found, however no data found for the same');
                                return false;
                            }

                            //HTML output buffer
                            var html = '';

                            //set the video wall title
                            html += '<div class="ziggeo_wall_title">' + ZiggeoWall[id].title + '</div>'

                            var parameters = 'tags=wordpress,comment,post_<?php echo $wall['postID']; ?>'; //do we want to show vidoes made in comments only or show all of them (made on this post)? - set to display comments only.

                            //To show the page we must first index videos..
                            ZiggeoApi.Videos.index(parameters, {
                                success: function (args, data) {
                                    if(data.length > 0) {
                                        //we got some videos back
                                        //go through videos

                                        //number of videos per page currently
                                        var currentVideosPageCount = 0;
                                        //total number of videos that will be shown
                                        var usedVideos = 0;
                                        //What page are we on?
                                        var currentPage = 0;
                                        //did any videos match the checks while listing them - so that we do not place multiple pages since the count stays on 0
                                        var newPage = true;

                                        for(i = 0, j = data.length, tmp=''; i < j; i++, tmp='') {

                                            if(ZiggeoWall[id].indexing.status === 'all') {
                                                tmp += '<ziggeo ' +
                                                            ' ziggeo-width=' + ZiggeoWall[id].videos.width +
                                                            ' ziggeo-height=' + ZiggeoWall[id].videos.height +
                                                            ' ziggeo-video="' + data[i].token + '"' +
                                                        '></ziggeo>';
                                                usedVideos++;
                                                currentVideosPageCount++;
                                            }
                                            else if(ZiggeoWall[id].indexing.status === 'rejected') {
                                               if(data[i].approved !== true) {
                                                    tmp += '<ziggeo ' +
                                                                ' ziggeo-width=' + ZiggeoWall[id].videos.width +
                                                                ' ziggeo-height=' + ZiggeoWall[id].videos.height +
                                                                ' ziggeo-video="' + data[i].token + '"' +
                                                            '></ziggeo>';                                                   
                                                    usedVideos++;
                                                    currentVideosPageCount++;
                                               }
                                            } 
                                            else { //approved
                                                if(data[i].approved === true) {
                                                    tmp += '<ziggeo ' +
                                                                ' ziggeo-width=' + ZiggeoWall[id].videos.width +
                                                                ' ziggeo-height=' + ZiggeoWall[id].videos.height +
                                                                ' ziggeo-video="' + data[i].token + '"' +
                                                            '></ziggeo>';
                                                    usedVideos++;
                                                    currentVideosPageCount++;
                                                }
                                            }

                                            //Do we need to create a new page?
                                            //We only create new page if there were any videos to add, otherwise if 1 video per page is set, we would end up with empty pages when videos are not added..
                                            if(currentVideosPageCount === 1 && newPage === true) {
                                                //we do
                                                currentPage++;

                                                //For slidewall we add next right away..
                                                if(ZiggeoWall[id].indexing.slideWall) {
                                                    if(currentPage > 1) {
                                                        html += '<div class="ziggeo_videowall_slide_next"  onclick="ziggeoShowWallPage(\'' + id + '\', ' + currentPage + ');"></div>';
                                                        html += '</div>';
                                                    }
                                                }
                                                
                                                html += '<div id="' + id + '_page_' + currentPage + '" class="ziggeo_wallpage">';

                                                //For slidewall we add back right away as well
                                                if(ZiggeoWall[id].indexing.slideWall) {
                                                    if(currentPage > 1) {
                                                        html += '<div class="ziggeo_videowall_slide_previous"  onclick="ziggeoShowWallPage(\'' + id + '\', ' + (currentPage-1) + ');"></div>';
                                                    }
                                                }

                                                html += tmp;
                                                tmp = '';
                                                newPage = false;                                                    
                                            }

                                            //combining the code if any
                                            if(tmp !== '') {
                                                html += tmp;
                                            }

                                            //Do we have enough of vidoes on this page and its time to create a new one?
                                            if(currentVideosPageCount === ZiggeoWall[id].indexing.perPage) {
                                                //Yup, we do
                                                if(ZiggeoWall[id].indexing.showPages) {
                                                    html += '</div>';                                                    
                                                }
                                                currentVideosPageCount = 0;
                                                newPage = true;
                                            }
                                        }

                                        //In case last page has less videos than per page limit, we need to apply the closing tag
                                        if(currentVideosPageCount < ZiggeoWall[id].indexing.perPage && newPage === false) {
                                            
                                            //For slidewall we add pages right away..
                                            /*if(ZiggeoWall[id].indexing.slideWall) {
                                                //if(currentPage > 1) { //add if not last
                                                    html += '<div class="ziggeo_videowall_slide_next"  onclick="ziggeoShowWallPage(\'' + id + '\', ' + (currentPage+1) + ');"></div>';
                                                //}
                                            }*/

                                            html += '</div>';
                                        }

                                        //Lets add pages if showPages is set
                                        if(ZiggeoWall[id].indexing.showPages) {
                                            for(i = 0; i < currentPage; i++) {
                                                html += '<div class="ziggeo_wallpage_number' + ((i===0) ? ' current' : '') + '" onclick="ziggeoShowWallPage(\'' + id + '\', ' + (i+1) + ',this);">' + (i+1) + '</div>';
                                            }
                                            html += '<br class="clear" style="clear:both;">';
                                        }

                                        //Lets add everything so that it is shown..
                                        wall.innerHTML = html;
                                    }
                                    else {
                                        //no results
                                        //follow the procedure for no videos (on no videos)
                                        console.log('No videos found matching the requested:' + args);
                                    }
                                   
                                    //lets show it:
                                    wall.style.display = 'block';
                                },
                                falure: function (args, error) {
                                    console.log('This was the error that we got back when seaching for ' + args +  ':' + error);
                                }
                            });
                        }
                    }
                    function ziggeoShowWallPage(id, page, current) {
                        //reference to wall
                        var wall = document.getElementById(id);

                        //lets check if wall is existing or not. If not, we break out and report it.
                        if(!wall) {
                            console.log('Exiting function. Specified wall is not present');
                            return false;
                        }
                        
                        var pageID = id + '_page_' + page;

                        var newPage = document.getElementById(pageID);

                        //Get all pages under current wall
                        var pages = wall.getElementsByClassName('ziggeo_wallpage');                            

                        //Hide all of the pages
                        for(i = 0, j = pages.length; i < j; i++) {
                            pages[i].style.display = 'none';
                        }

                        //set the visual indicator of what page is selected
                        var pageNumbers = wall.getElementsByClassName('ziggeo_wallpage_number');

                        //This is only active if we show page numbers / page buttons
                        if(current) {
                            //reset style of the page number buttons
                            for(i = 0, j = pageNumbers.length; i < j; i++) {
                                pageNumbers[i].className = 'ziggeo_wallpage_number';
                            }

                            //adding .current class to the existing list of classes
                            current.className = 'ziggeo_wallpage_number current';
                        }

                        newPage.style.display = 'block';
                    }
                </script>
                <?php

                //Video wall will by default only show when the video comment is submitted, unless this is overridden by the `show` parameter
                if( !isset($wall['show']) ) {
                    //wait for video submission first
                    ?>
                    <script type="text/javascript">
                        //just to make sure that it is available
                        if(ZiggeoApi) {
                            ZiggeoApi.Events.on("submitted", function (data) {
                                ziggeoShowVideoWall('<?php echo $wallID; ?>');
                            });
                        }
                        //lets wait for a second and try again.
                        else {
                            setTimeout( function(){
                                ZiggeoApi.Events.on("submitted", function (data) {
                                    ziggeoShowVideoWall('<?php echo $wallID; ?>');
                                });
                            }, 10000 ); //10 seconds should be enough for page to load and we do not need to have this set up right away.
                        }
                    </script>
                    <?php                    
                }
                else {
                    //video wall must be shown right away..
                    ?>
                    <script type="text/javascript">
                        jQuery(document).ready( function () {
                            ziggeoShowVideoWall('<?php echo $wallID; ?>');                            
                        });
                    </script>
                    <?php
                }

                //closing videowall div
                $ret .= '</div>';
            }
            elseif($tag === 'ziggeoform')
            {
                $ret = '<b> Here a ' . $tag . ' code would be placed</b>';
            }
            //one of the players/recorders/uploaders
            else {
                //Apply ziggeo prefixes - only needed for <ziggeo> code
                $template = ziggeo_parameter_prep($template);

                if( isset($options, $options['beta']) && $tag === 'ziggeoplayer' ) { 
                    $ret = '<ziggeoplayer ziggeo-theme="modern" ' . $template . '></ziggeoplayer>';
                }
                else {
                    $ret = '<ziggeo ' . $template . '></ziggeo>';
                }                
            }

        }

        return $ret;
    }
}

//We are updating this in such a way that we will keep the old calls, so that we have backwards compatibility, but in the same time, we are adding another call that will check for us if there are any tags matching new templates. We must do it like this, since using regex we will be able to find this in all locations that we want, while if we use shortcode, it will only work (out of the box) if the shortcode is within the section covered by 'the_content' filter.
function ziggeo_content_filter($content) {

    //Match the current setups - the ones done by previous versions
    $content = preg_replace_callback("|\\[ziggeo\\](.*)\\[/ziggeo\\]|", 'ziggeo_content_replace', $content);

    //Match the new setups - must be made after the above, since this one will match that one as well..

    //matching new templates with old way of calling them in case someone does the same..
    $content = preg_replace_callback("|\\[ziggeo(.*)\\](.*)\\[/ziggeo(.*)\\]|", 'ziggeo_content_replace_templates', $content);

    //finally we do a check for the latest way of doing it only.
    $content = preg_replace_callback("|\\[ziggeo(.*)\\]|", 'ziggeo_content_replace_templates', $content);

    //check to make sure that we get even [ziggeo calls without end bracket and show the embedding matching the call as much as possible instead of an error on a page
    $content = preg_replace_callback("|\\[ziggeo*([^\s\<]+)|", 'ziggeo_content_replace_templates', $content);

    return $content;
}

//Function to process parameters. We send it which ones have to be in and which one are currently set and it sends us back a string that we can use as a template
//It will also (TODO) check if we are setting same parameters to remove duplicates, while seeing if one should be used over the other..
function ziggeo_parameter_processing($requiredAtt, $process, $stripDuplicates = false)
{
    $processed = $process; //for now

    foreach ($requiredAtt as $req => $value) {
        if( stripos($process, $req) === false )
        {
            $processed .= ' ' .  $req;
            
            if($value === '')            { $processed .= '=""';  } //if it is empty string
            elseif($value === false)    { $processed .= '=false'; } //if it is false
            elseif(is_array($value))    { $processed .= '="' . implode(',', $value) . '"'; }
            elseif($value !== true)        { $processed .= '=' . $value; } //if it is number (since it already passed the above..)
        }
    }

    //Seems that if customers use "" within the visual editor, it will change quote to &#8221; and &#8243; so lets clean that up..
    $processed = str_replace( array('&#8221;', '&#8243;'), '"', $processed);
    $processed = str_replace( array('&#8217;', '&#8242;'), "'", $processed);

    return $processed;
}

//Function to search for parameters without "ziggeo-" and apply the same to them.
function ziggeo_parameter_prep($data) {

    $tmp_str = explode(' ', $data);
    $tmp_str2 = '';

    foreach($tmp_str as $key => $value) {
        $value = trim($value);
        if( $value !== '' && $value !== ']' && $value !== '""'&& $value !== '"'
            && $value !== 'player' && $value !== 'recorder' && $value !== 'rerecorder') {

            if( stripos($value, 'ziggeo-') > -1 ) {
                //seems that ziggeo- prefix is already present.. should we do something then, or just skip it?
            }
            else {
                $tmp_str2 .= ' ziggeo-' . $value;
            }
        }
    }

    return $tmp_str2;
}

//function to get the nice aray of the video wall parameters and values, so that we do not cluter the main function too much
function ziggeo_wall_parameter_values($toParse){

    $parsed = array();

    //First we are grabbing the parameters that can and probably will include spaces within them.

    //VideoWall Title
    if( ($t = stripos($toParse, ' title=')) > -1 ) {
        //Lets get the title then
        $parsed['title'] = substr($toParse, $t+8, stripos($toParse, "'", $t+8) - ($t + 8));

        //get parameters and values prior to title parameter
        $tmp = substr($toParse, 0, $t) . ' ';
        //get values after the title parameter and its values ( position + (starting space + parameter + = ) + length of parameter value + quotes )
        $tmp .= substr($toParse, $t + 8 + strlen($parsed['title']) + 2);

        $toParse = $tmp;
    }

    //No videos message
    if( ($t = stripos($toParse, ' message=')) > -1 ) {

        //Lets get the message then
        $parsed['message'] = substr($toParse, $t+10, stripos($toParse, "'", $t+10) - ($t + 10) );

        //get parameters and values prior to message parameter
        $tmp = substr($toParse, 0, $t) . ' ';
        //get values after the message parameter and its values ( position + (starting space + parameter + = ) + length of parameter value + quotes )
        $tmp .= substr($toParse, $t + 10 + strlen($parsed['message']) + 2);

        $toParse = $tmp;
    }

    //no videos template_name
    if( ($t = stripos($toParse, ' template_name=')) > -1 ) {
        //Lets get the template_name then
        $parsed['template_name'] = substr($toParse, $t+16, stripos($toParse, "'", $t+16) - ($t + 16));

        //get parameters and values prior to template_name parameter
        $tmp = substr($toParse, 0, $t) . ' ';
        //get values after the template_name parameter and its values ( position + (starting space + parameter + = ) + length of parameter value + quotes )
        $tmp .= substr($toParse, $t + 16 + strlen($parsed['template_name']) + 2);

        $toParse = $tmp;
    }

    //We can now split the rest with explode()

    $tmp = explode(' ', $toParse);

    foreach($tmp as $key => $value) {
        $value = trim($value);
        if( $value !== '' && $value !== ']' && $value !== '""'&& $value !== '"'
            && $value !== 'wall') {
                //explode on = and trim ' and "
                $t = explode('=', $value);
                if(isset($t[1])) {
                    $parsed[$t[0]] = trim($t[1], "'");
                }
                else {
                    $parsed[$t[0]] = true;
                }
        }
    }

    return $parsed;
}
?>