<?php

namespace Enqueue\Test;

trait RetryTrait
{
    public function runBare()
    {
        $e = null;

        $numberOfRetires = $this->getNumberOfRetries();
        if (false == is_numeric($numberOfRetires)) {
            throw new \LogicException(sprintf('The $numberOfRetires must be a number but got "%s"', var_export($numberOfRetires, true)));
        }
        $numberOfRetires = (int) $numberOfRetires;
        if ($numberOfRetires <= 0) {
            throw new \LogicException(sprintf('The $numberOfRetires must be a positive number greater than 0 but got "%s".', $numberOfRetires));
        }

        for ($i = 0; $i < $numberOfRetires; ++$i) {
            try {
                parent::runBare();

                return;
            } catch (\PHPUnit_Framework_IncompleteTestError $e) {
                throw $e;
            } catch (\PHPUnit_Framework_SkippedTestError $e) {
                throw $e;
            } catch (\Throwable $e) {
                // last one thrown below
            } catch (\Exception $e) {
                // last one thrown below
            }
        }

        if ($e) {
            throw $e;
        }
    }

    /**
     * @return int
     */
    private function getNumberOfRetries()
    {
        $annotations = $this->getAnnotations();

        if (isset($annotations['method']['retry'][0])) {
            return $annotations['method']['retry'][0];
        }

        if (isset($annotations['class']['retry'][0])) {
            return $annotations['class']['retry'][0];
        }

        return 1;
    }
}
