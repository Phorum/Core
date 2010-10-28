
/* overall style */

body {
    background-color: {default_background_color};
    color: {default_font_color};
}

#phorum {
    font-family: {default_font};
    font-size: {base_font_size};
    color: {default_font_color};
    max-width: {max_width};
    margin: auto;
}

/* HTML level styles */

img {
    vertical-align: top;
    border: none;
}

#phorum table.list {
    width: 100%;
    margin-bottom: 4px;
    border: 1px solid {border_color};
    border-bottom: 0;
}


#phorum table.list th  {
    color: {border_font_color};
    background-color: {border_color};
    font-size: {font_small};
    padding: 5px;
}

#phorum table.list th a {
    color: {border_font_color};
}

#phorum table.list td {
    background-color: {default_background_color};
    padding: 8px;
    border-bottom: 1px solid {border_color};
    font-size: {font_small};
}

#phorum table.list td.alt {
    background-color: {alt_background_color};
}

#phorum table.list td.current {
    background-color: {highlight_background_color};
}

#phorum table.list td p {
    margin: 4px 8px 16px 4px;
}

#phorum table.list td h3 {
    margin: 0;
}

#phorum table.list td h4 {
    font-size: {font_large};
    margin: 0;
    font-weight: normal;
}

#phorum table.list td span.new-indicator {
    color: {new_color};
    font-size: 80%;
    font-weight: normal;
}

#phorum a {
    color: {link_color};
}

#phorum a:hover {
    color: {link_hover_color};
}

#phorum a.icon {
    background-repeat: no-repeat;
    background-position: 1px 2px;
    padding: 4px 10px 2px 0;
    font-weight: normal;
    white-space: nowrap;
}

#phorum h1 {
    margin: 5px 0 0 0;
    font-size: {font_xx_large};
}

#phorum h2 {
    margin: 0;
    font-size: {font_large};
    font-weight: normal;
}

#phorum h4 {
    margin: 0 0 5px 0;
}

#phorum hr {
    height: 1px;
    border: 0;
    border-top: 1px solid {border_color};
}

/* global styles */

#phorum div.generic {
    padding: 8px;
    background-color: {alt_background_color};
    border: 1px solid {border_color};
    overflow: hidden;
}

#phorum div.generic-lower {
    padding: 8px;
    margin-bottom: 8px;
}

#phorum div.paging {
    float: right;
}

#phorum div.paging a {
    font-weight: bold;
    margin: 0 4px 0 4px;
    padding: 0 0 1px 0;
}

#phorum div.paging img{
    vertical-align: bottom;
}

#phorum div.paging strong.current-page {
    margin: 0 4px 0 4px;
}

#phorum div.nav {
    font-size: {font_small};
    margin: 0 0 5px 0;
    line-height: 20px;
}

#phorum div.nav-right {
    float: right;
}

#phorum div.information {
    padding: 8px;
    border: 1px solid #62a762;
    background-color: #e6ffe6;
    margin-bottom: 8px;
}

#phorum div.notice {
    padding: 8px;
    background-color: {alt_background_color};
    border: 1px solid {border_color};
    margin-bottom: 8px;
}

#phorum div.warning {
    /* does not use template values on purpose */
    border: 1px solid #A76262;
    background-color: #FFD1D1;
    padding: 8px;
    margin-bottom: 8px;
}

#phorum div.attachments {
    background-color: {default_background_color};
    margin-top: 8px;
    padding: 16px;
    border: 1px solid {border_color};
}

#phorum span.new-flag {
    color: {new_color};
}

#phorum a.message-new {
    font-weight: bold;
}

#phorum table.menu td {
    vertical-align: top;
}

#phorum table.menu td.menu {
    font-size: {font_small};
    padding: 0 8px 0 0;
}

#phorum table.menu td.menu ul {
    list-style: none;
    padding: 0;
    margin: 4px 0 8px 8px;
}

#phorum table.menu td.menu ul li {
    margin: 0 0 4px 0;
}

#phorum table.menu td.menu ul li a {
    text-decoration: none;
}

#phorum table.menu td.menu ul li a.current {
    font-weight: bold;
}

#phorum table.menu td.menu span.new {
    color: {new_color};
}

#phorum table.menu td.content {
    width: 100%;
    padding: 0;
}

#phorum table.menu td.content h2 {
    margin: 0 0 8px 0;
    color: {border_font_color};
    background-color: {border_color};
    padding: 4px;
}

#phorum table.menu td.content div.generic {
    margin: 0 0 8px 0;
}

#phorum table.menu td.content dl {
    margin: 0;
    padding: 0;
}

#phorum table.menu td.content dt {
    font-weight: bold;
}

#phorum table.menu td.content dd {
    padding: 4px;
    margin: 0 0 8px 0;
}

#phorum fieldset {
    border: 0;
    padding: 0;
    margin: 0;
}

#phorum textarea.body {
    font-family: {default_font};
    width: 100%;
    border: 0;
}

#phorum table.form-table {
    width: 100%;
}

/* header styles */

#phorum #logo {
    height: 46px;
    background-color: {logo_background_color};
    vertical-align: bottom;
}

#phorum #logo img {
    margin: 16px 0 0px 16px;
}

#phorum #top {
    padding: 8px 8px 8px 0;
    margin: 0 16px 16px 0;
}

#phorum #top-right {
    float: right;
    text-align: right;
}

#phorum #description {
    margin: 8px 8px 0 0;
    padding-right: 32px;
    font-size: {font_small};
}

#phorum #breadcrumb {
    border-bottom: 1px solid {breadcrumb_border_color};
    border-top: 0;
    padding: 5px;
    font-size: {font_small};
}

#phorum #user-info {
    font-size: {font_small};
    margin: 0 0 4px 0;
    text-align: right;
}

#phorum #user-info a {
    padding: 4px 0 2px 0;
    background-repeat: no-repeat;
    background-position: 1px 2px;
}

#phorum #user-info img {
    border-width : 0;
    margin: 4px 3px 0 0;
}

#phorum #user-info small a{
    margin: 0;
    padding: 0;
    display: inline;
}

#phorum div.attention {
    /* does not use template values on purpose */
    padding: 24px 8px 24px 64px;
    border: 1px solid #A76262;
    background-color: #FFD1D1;
    color: Black;
    margin: 8px 0 8px 0;
}

#phorum div.attention a {
    /* does not use template values on purpose */
    color: #68312C;
    padding: 2px 2px 2px 21px;
    display: block;
    background-repeat: no-repeat;
    background-position: 1px 2px;
}

#phorum #right-nav {
    float: right;
}

#phorum #search-area {
    padding: 8px 8px 8px 32px;
    background-repeat: no-repeat;
    background-position: 8px 12px;
    margin: 0 16px 8px 0;
}


#phorum #header-search-form {
    display: inline;
}

#phorum #header-search-form a {
    font-size: {font_xx_small};
}



/* Read styles */

#phorum div.message div.generic {

}

#phorum td.message-user-info {
    font-size: {font_small};
    white-space: nowrap;
}

#phorum div.message-author {
    font-size: {font_large};
    font-weight: bold;
    margin-bottom: 5px;
}

#phorum div.message-author small {
    font-size: {font_xx_small};
    font-weight: normal;
    margin: 0 0 0 16px;
}

#phorum div.message-subject {
    font-weight: bold;
    font-size: {font_small};
}

#phorum div.message-body {
    padding: 16px;
    margin: 0 0 16px 0;
    border: 1px solid {border_color};
    border-top: 0;
    overflow: hidden; /* makes the div extend around floated elements */
}

#phorum div.message-body br {
    clear: both;
}

#phorum div.message-date {
    font-size: {font_small};
}

#phorum div.message-moderation {
    margin-top: 8px;
    font-size: {font_small};
    border-top: 0;
    padding: 6px;
    background-color: {alt_background_color};
    border: 1px solid {border_color};
    line-height: 20px;
}

#phorum div.message-options {
    margin-top: 8px;
    text-align: right;
    font-size: {font_small};
    clear: both;
}

#phorum #thread-options {
    margin: 8px 0 32px 0;
    background-color: {alt_background_color};
    border: 1px solid {border_color};
    padding: 8px;
    text-align: center;
}

/* Changes styles */

#phorum span.addition {
    /* does not use template values on purpose */
    background-color: #CBFFCB;
    color: black;
}

#phorum span.removal {
    /* does not use template values on purpose */
    background-color: #FFCBCB;
    color: black;
}

/* Posting styles */

#phorum #post {
    clear: both;
}

#phorum #post ul {
    margin: 2px;
}

#phorum #post ul li {
    font-size: {font_small};
}

#phorum #post-body {
    border: 1px solid {border_color};
    background-color: {default_background_color};
    padding: 8px;
}

#phorum #post-moderation {
    font-size: {font_small};
    float: right;
    border: 1px solid {border_color};
    background-color: {post_moderation_background_color};
    padding: 8px;
}

#phorum #post-buttons {
    text-align: center;
    margin-top: 8px;
}

#phorum div.attach-link {
    padding: 4px 10px 2px 21px;
    font-size: {font_small};
    font-weight: normal;
}

#phorum #attachment-list td {
    font-size: {font_small};
    padding: 6px;
}

#phorum #attachment-list input {
    font-size: {font_xx_small};
}


/* PM styles */

#phorum input.rcpt-delete-img {
    vertical-align: bottom;
}

#phorum div.pm {
    padding: 8px;
    background-color: {alt_background_color};
    border: 1px solid {border_color};
    border-bottom: 0;
}

#phorum div.pm div.message-author {
    font-size: {font_small};
}

#phorum .phorum-gaugetable {
    margin-top: 10px;
    border-collapse: collapse;
}

#phorum .phorum-gauge {
    border: 1px solid {border_color};
    background-color: {default_background_color};
}

#phorum .phorum-gaugeprefix {
    border: none;
    background-color: {default_background_color};
    padding-right: 10px;
}


/* Profile styles */

#phorum #profile div.icon-user {
    background-repeat: no-repeat;
    background-position: 0px 2px;
    padding: 0px 0 0px 21px;
    font-size: {font_large};
    font-weight: bold;
    margin-bottom: 5px;
}

#phorum #profile div.icon-user small {
    font-size: {font_xx_small};
    font-weight: normal;
    margin: 0 0 0 16px;
}

#phorum #profile dt {
    font-weight: bold;
}

#phorum #profile dd {
    padding: 4px;
    margin: 0 0 8px 0;
}


/* Search Styles */

#phorum #search-form {
    margin-bottom: 35px;
}

#phorum #search-form form {
    font-size: {font_small};
}

#phorum div.search {
    background-color: {default_background_color};
}

#phorum div.search-result {
    font-size: {font_small};
    margin-bottom: 20px;
}

#phorum div.search-result h4 {
    font-size: {font_x_large};
    margin: 0;
}

#phorum div.search-result h4 small {
    font-size: {font_x_small};
}

#phorum div.search-result blockquote {
    margin: 3px 0 3px 0;
    padding: 0;
}

/* Footer styles */

#phorum #footer-plug {
    margin-top: 26px;
    font-size: {font_xx_small};
    text-align: center;
}









/*   BBCode styles  */

#phorum blockquote.bbcode {
    font-size: {font_small};
    margin: 0 0 0 10px;
}

#phorum blockquote.bbcode>div {
    margin: 0;
    padding: 5px;
    border: 1px solid {quote_border_color};
    overflow: hidden;
}

#phorum blockquote.bbcode strong {
    font-style: italic;
    margin: 0 0 3px 0;
}

#phorum pre.bbcode {
    border: 1px solid {pre_border_color};
    background-color: {pre_background_color};
    padding: 8px;
    overflow: auto;
}


