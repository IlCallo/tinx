<?php

namespace Ajthinking\Tinx\Models;

use Exception;
use Throwable;
use Illuminate\Support\Facades\File;

class ModelValidator
{
    /**
     * @param string $filePath
     * @param string $fullClassName
     * @return void
     * */
    public function __construct($filePath, $fullClassName)
    {
        $this->filePath = $filePath;
        $this->fullClassName = $fullClassName;
        $this->hasTablelessModels = (bool) config('tinx.tableless_models');
    }

    /**
     * @param string $filePath
     * @param string $fullClassName
     * @return static
     * */
    public static function make($filePath, $fullClassName)
    {
        return new static($filePath, $fullClassName);
    }

    /**
     * @return bool
     * */
    public function fails()
    {
        return false === $this->passes();
    }

    /**
     * @return bool
     * */
    public function passes()
    {
        try {
            if (is_dir($this->filePath)) {
                return false;
            }
            if ($this->isAbstractClass()) {
                return false;
            }
            if ($this->cannotInstantiate()) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * */
    private function isAbstractClass()
    {
        return preg_match($this->getAbstractClassRegex(), $this->getFileContents());
    }

    /**
     * Matches "abstract class ClassName {" (and similar variations).
     *
     * @return string
     * */
    private function getAbstractClassRegex()
    {
        $start = $end = '/';
        $wordBoundary = '\b';
        $oneOrMoreSpaces = '\s+';
        $oneOrMoreWordsOrSpaces = '[\w|\s]+';
        $ignoreCase = 'i';

        return
            $start.
            $wordBoundary.'abstract'.$wordBoundary.$oneOrMoreSpaces.
            $wordBoundary.'class'.$wordBoundary.$oneOrMoreWordsOrSpaces.
            '{'.
            $end.
            $ignoreCase;
    }

    /**
     * @return string
     * */
    private function getFileContents()
    {
        return File::get($this->filePath);
    }

    /**
     * @return bool
     * */
    private function cannotInstantiate()
    {
        return false === $this->canInstantiate();
    }

    /**
     * @return bool
     * */
    private function canInstantiate()
    {
        try {
            call_user_func([$this->fullClassName, 'first']) ?: app($this->fullClassName);
            return true;
        } catch (Throwable $e) {
            return $this->canInstantiateOnError();
        } catch (Exception $e) {
            return $this->canInstantiateOnError();
        }
    }

    /**
     * @return bool
     * */
    private function canInstantiateOnError()
    {
        if (false === $this->hasTablelessModels) {
            return false;
        }

        try {
            app($this->fullClassName);
            return true;
        } catch (Throwable $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}
