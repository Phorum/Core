<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
////////////////////////////////////////////////////////////////////////////////

    if(!defined("PHORUM_ADMIN")) return;

    /**
     * Allow module authors to have a custom HTML injected anywhere in the
     * current Phorum admin menu.
     *
     * Module authors can show e.g. a custom menu block right after the top
     * "Main Menu" if they want so.
     *
     * An instance of this class is passed to the "admin_menu" hook. Use the
     * following methods to inject your HTML:
     *
     * <code><pre>
     * $pos->appendAt(1, 'your html goes here');
     * $pos->appendLast('your html goes here');
     * </pre></code>
     *
     * Module authors are advised to use the PhorumAdminMenu class to build a
     * menu and use it's getHtml() method to position it were desired.
     *
     * <code><pre>
     * $menu = new PhorumAdminMenu("My menu");
     * $menu->addCustom('Phorum rulez', 'http://phorum.org/', 'Rocks', '_blank');
     *
     * $pos->appendAt(1, $menu->getHtml());
     * </pre></code>
     *
     * Multiple HTML can be injected into any position, first come first serve.
     * To influence this order, you need to take advantage of the hook priority
     * mechanism.
     */
    class PhorumAdminMenuHookPosition {
        /**
         * The array with all positions and their HTML content.
         * 
         * @var array  Defaults to array(). 
         */
        private $_aPos = array();
        /**
         * Special handling for the last position.
         * 
         * @var array  Defaults to array(). 
         */
        private $_aLastPos = array();
        /**
         * Helper member to easily process all injects positions.
         * 
         * @var int  Defaults to 0.
         * @see fetchAndRemoveNext()
         */
        private $_iNext = 0;
        /**
         * Append the HTML at this position.
         *
         * @param int    $iPos  0 is always before the Phorums "Main Menu"
         * @param string $sHtml 
         * 
         * @return PhorumAdminMenuHookPosition
         */
        public function appendAt($iPos, $sHtml) {
            $iPos = (int)$iPos;

                if (!isset($this->_aPos[$iPos])) {
                $this->_aPos[$iPos] = array();
            }

            $this->_aPos[$iPos][] = $sHtml;

            return $this;
        }

        /**
         * Append the HTML for the last position.
         *
         * The last position is always after the last hardcoded Phorum admin
         * menus.
         *
         * @param string $sHtml 
         * 
         * @return PhorumAdminMenuHookPosition
         */
        public function appendLast($sHtml) {
            $this->_aLastPos[] = $sHtml;

            return $this;
        }
        /**
         * Fetches the HTML from the requested positon and removes it from the
         * internal list (this means it can't be fetched twice).
         * 
         * @param int $iPos 
         * 
         * @return string   HTML (may be empty)
         */
        public function fetchAndRemoveAt($iPos) {
            $iPos = (int) $iPos;
            if (!isset($this->_aPos[$iPos])) {
                return '';
            }
            $sHtml = join('', $this->_aPos[$iPos]);
            unset($this->_aPos[$iPos]);
            return $sHtml;
        }
        /**
         * Fetches the HTML from any remaining position.
         *
         * In case a position has not been yet processed (e.g. the Phorum Admin
         * menu structure has changed and there are now less menus), don't
         * forget those and fetch them.
         *
         * This also returns the last position, always at the end.
         * 
         * @param int $iPos 
         * 
         * @return string   HTML (may be empty)
         */
        public function fetchAndRemoveRemaining() {
            $sHtml = '';
            foreach ($this->_aPos as $mPos => $aHtml) {
                $sHtml .= $this->fetchAndRemoveAt($mPos);
            }
            return $sHtml . $this->fetchAndRemoveLast();
        }
        /**
         * Fetches the HTML from the requested positon and removes it from the
         * internal list (this means it can't be fetched twice).
         * 
         * @return string   HTML (may be empty)
         */
        public function fetchAndRemoveLast() {
            $sHtml = join('', $this->_aLastPos);
            $this->_aLastPos = array();
            return $sHtml;
        }
        /**
         * Uses an internal counter to fetch the HTML from the next position.
         * The internal counter starts at 0 and is incremented everytime this
         * method is called, allowing to process all positions (except the
         * special 'last' ones)
         * 
         * @return string   HTML (may be empty)
         */
        public function fetchAndRemoveNext() {
            return $this->fetchAndRemoveAt($this->_iNext++);
        }
        /**
         * Order all the positions by their position number. This ensures that
         * HTML at positions not yet processed remains at least in their
         * requested order.
         */
        public function reorderPositions() {
            ksort($this->_aPos);
        }
    }

?>
