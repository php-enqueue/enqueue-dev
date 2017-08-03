<?php

namespace Enqueue\Test;

trait RetryTrait
{
    public function runBare()
    {
        $e = null;

        $numberOfRetires = $this->getNumberOfRetries();
        for ($i = 0; $i < $numberOfRetires; ++$i) {
            try {
                parent::runBare();

                return;
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

        if (isset($annotations['method']['retry'])) {
            return $annotations['method']['retry'];
        }

        if (isset($annotations['class']['retry'][0])) {
            return $annotations['class']['retry'][0];
        }

        return 1;
    }
}
