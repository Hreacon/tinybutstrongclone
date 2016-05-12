<?php

// VERSION 1.2
// 4/17/2015
// Nicholas Jensen
// coded for nwautox
// V1.2
// Changelog: Added support for self closing html tags
// V1.1
// Changelog: Added support for multidimensional arrays
class site {

    var $template;
    var $header;
    var $footer;
    var $endchar = ']';
    var $startchar= '[';

    function site() {
        $this->template = false;
        $this->header = false;
        $this->footer = false;
    } // end func site

    function openFile($filename) {
        // TODO check to see if file exists or catch
        return fopen($filename, "r");
    }

    function readFile($file, $filename) {
        return fread($file,filesize($filename));
    }

    function newFile($filename) {
        // open file
        $file = $this->openFile($filename);
        // read file
        $code = $this->readFile($file, $filename);
        // replace variables
        $code = $this->compileVar($code);
        // replace html
        $code = $this->compileSpecChars($code);
        // return code
        return $code;
    }

    function getEndIndex($code, $endchar, $index) {
        return strpos($code, $endchar, $index);
    }

    function getCommand($code, $index, $endchar) {
        // TODO protect against random [
        $end     = strpos($code, $endchar, $index);
        $command = substr($code,$index+1,$end-$index-1);
        return $command;
    }

    function getCommandBrace($code, $index) {
        return $this->getCommand($code,$index,$this->endchar);
    }

    function getDataFromCommand($code, $index, $command) {
        return substr($this->getCommandBrace($code, $index), strlen($command)+1);
    }

    function replaceData($code, $startIndex, $endIndex, $data) {
        return substr_replace($code, $data, $startIndex, $endIndex-$startIndex+1);
    }
    function replaceDataWithEndChar($code, $index, $endchar, $data) {
        $endindex = $this->getEndIndex($code, $endchar, $index);
        $code = $this->replaceData($code, $index, $endindex, $data);
        return $code;
    }

    function findCmd($code, $commandType, $start) {
        $count = 0;
        $maxcount = 100; // dont try more than 100 times
        while($count<$maxcount) {
            // get index
            $index = strpos($code, $this->startchar, $start);
            if( $index === false ) // if there are no more tags or matching commands
                    return 'done'; // exit with error

            // check for command type
            $command = $this->getCommandBrace($code, $index);
            if( substr($command, 0, strlen($commandType)) == $commandType ) // command matches
                return $index;
            else // command doesn't match
                $start = $index+1;
        }// end while
    } // end function findcmd

    function compileVar($code) {
        $index = 0;
        $done = 0;
        $command = 'var';

        while( !$done ) {
            // get index of [
            $index = $this->findCmd($code, $command, $index);
            if( $index !== 'done' ) // findCmd returns done when no commands
            { // replace text
                $varname = $this->getDataFromCommand($code, $index, $command);
                global ${$varname};
                $code = $this->replaceDataWithEndChar($code,$index,$this->endchar,${$varname});
            } else { // end if
                $done = 1; // end while
            }
        }// end while

        return $code;
    } // end function compileVar

    function compileSpecChars($code) {
        $index = 0;
        $done = 0;
        $command = 'html';
        while( !$done ) {
            $index = $this->findCmd($code, 'html', $index);
            if( $index !== 'done' ) // findCmd returns done when no commands
            {
                // get the data from the command
                $data = $this->getDataFromCommand($code,$index,$command);
                // get replacement data
                $data = htmlspecialchars($data);
                // replace data
                $code = $this->replaceDataWithEndChar($code,$index,$this->endchar,$data);
            } else {
                $done = 1; // end while
            }
        }// end while

        return $code;
    }// end func compileSpecChars

    function loadTemplate($filename) {
        $this->template =  $this->newFile($filename);
    }

    function loadHeader($filename) {
        $this->header = $this->newFile($filename);
    }

    function loadFooter($filename) {
        $this->footer = $this->newFile($filename);
    }

    // only works with single dimensional arrays
    function mergeArray($blockName, $array) {
        $done = 0;
        $command='';

        $code = $this->getAllCode(); // get all code

        do {  // find the block with the html designation
            $index = $this->findCmd($code, $blockName, 0); // finds the command with the block name
            if ($index != 'done') {
                $command = $this->getCommandBrace($code, $index); // gets the command

                if (strpos($command, ';block=') > 0) // checks for tag data
                    $done = true;
            } else {
                echo 'Error merging block, block not found: ' . $blockName . '<br><pre>';
                var_dump($array);
                exit();
                $done = true;
            }
        } while(!$done);

        $tag = substr($command,strpos($command,'=')+1); // get the html tag

        $firsthtml = substr($code,0,$index); // get all html before the block tag
        $lasthtml = substr($code, $index); // get all html after the block tag
        $startTag = '<'.$tag;
        $endTag = '</' . $tag . '>';
        $startBlockIndex = strrpos($firsthtml,$startTag); // get the position of the html tag that matches

        $tagsToTry[] = '/>';
       
        $i = 0;
        // Doesn't work with self ending tags. In that case, we need to check the endBlockIndex
        $endBlockIndex = strpos($lasthtml, $endTag); // find the end tag
        while( $endBlockIndex === false ) { // did not find the tag
            $endBlockIndex = strpos($lasthtml, $tagsToTry[$i]); // try other closing methods
            $endTag = $tagsToTry[$i];
            $i++;
        }

        $endBlockIndex = strlen($firsthtml)+$endBlockIndex+strlen($endTag); // set the end position relative to full html and the tag, to get the full block of code
        $block = substr($code,$startBlockIndex,$endBlockIndex-$startBlockIndex);// get the block

        // take block, replace text, concatenate, repeat

        $newCode = '';

        // This only worked for single dimensional arrays
        // replace block text
        foreach ($array as $key => $val) {
            // find key denotation
            $newBlock = $block;

            if( !is_array($val) ) { // single dimensional array
                $newBlock = $this->compileCommand($newBlock, $blockName . '.key', $key);
                $newBlock = $this->compileCommand($newBlock, $blockName . '.val', $val);
            } else { // val is array
                foreach( $val as $key => $val ) {
                    $newBlock = $this->compileCommand($newBlock,$blockName.'.'.$key, $val);
                }
            }

            $newCode .= $newBlock;
        }

        // if val is array
        // loop through array and replace data for each val in that array with key as name
        // if val is not array, loop through current block and replace data with key/val

        // replace text in overall code
        // find if the code is in the header, template or footer
        if ($startBlockIndex < strlen($this->header)) { // if in header
            $this->header = $this->replaceData($this->header, $startBlockIndex, $endBlockIndex, $newCode);
        } else { // its past the header, subtract the indicies from the header length to make it easier to work with
            $startBlockIndex = $startBlockIndex - strlen($this->header);
            $endBlockIndex = $endBlockIndex - strlen($this->header);
            if($startBlockIndex < strlen($this->template)) { // if in template
                $this->template = $this->replaceData($this->template,$startBlockIndex,$endBlockIndex,$newCode);
            } else { // must be in footer
                $startBlockIndex = $startBlockIndex - strlen($this->template);
                $endBlockIndex = $endBlockIndex - strlen($this->template);
                $this->footer = $this->replaceData($this->footer,$startBlockIndex,$endBlockIndex,$newCode);
            }
        }

    } // end function mergeArray

    // replace all commands with the data provide
    function compileCommand($code, $command, $data) {
        do {
            $index = $this->findCmd($code, $command, 0); // find val command index
            if ($index != "done")
                $code = $this->replaceDataWithEndChar($code, $index, $this->endchar, $data); // replace data
        } while( $index != "done");
        return $code;
    }

    function getAllCode() {
        return $this->header.$this->template.$this->footer;
    }

    function show() {
        //if( $this->header OR $this->template OR $this->footer ) // if none have been set it will read false
            echo $this->getAllCode();
       // else
    }
} // end class
?>