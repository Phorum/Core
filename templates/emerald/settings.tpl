{! --- defines are used by the engine and vars are used by the template --- }

{! --- How many px to indent for each level --- }
{DEFINE indentmultiplier 20}

{! --- This is used to load the message-bodies in the message-list for that template if set to 1 --- }
{DEFINE bodies_in_list 0}

{! --- This is used the number of page numbers shown on the list page in the paging section (eg. 1 2 3 4 5) --- }
{DEFINE list_pages_shown 5}

{! --- Define on what page notifications should be displayed ---- }
{DEFINE show_notify_for_pages "index,list,cc"}

{! -- This is the image for the gauge bar to show how full the PM box is -- }
{VAR gauge_image "templates/emerald/images/gauge.gif"}

{VAR template_dir "emerald"}


{! -- Fonts -- }

{VAR default_font "Arial"}
{VAR base_font_size "medium"} {! -- Need this for IE -- }

{VAR font_xx_large "145%"}
{VAR font_x_large  "125%"}
{VAR font_large    "115%"}
{VAR font_small     "85%"}
{VAR font_x_small   "75%"}
{VAR font_xx_small  "65%"}


{! -- Sizes -- }
{VAR max_width "900px"}  {! -- No effect in IE 6 -- }


{! -- colors -- }
{VAR body_color "White"}
{VAR default_font_color "Black"}
{VAR default_background_color "White"}
{VAR alt_background_color "#edf2ed"} {! -- should compliment default_background_color -- }
{VAR highlight_background_color "#f0f7f0"} {! -- should compliment the two above -- }
{VAR border_color "#4d894d"}
{VAR border_font_color "White"}
{VAR quote_border_color "#808080"}
{VAR pre_border_color "#C4C6A2"}
{VAR pre_background_color "#FEFFEC"}
{VAR link_color "#355F35"}
{VAR link_hover_color "#709CCC"}
{VAR new_color "red"}
{VAR logo_background_color "#78ad78"}
{VAR breadcrumb_border_color "#b6b6b6"}
{VAR post_moderation_background_color "#fffdf6"}


