<?php
/**
 * This file is part of PHP Mess Detector.
 *
 * Copyright (c) Manuel Pichler <mapi@phpmd.org>.
 * All rights reserved.
 *
 * Licensed under BSD License
 * For full copyright and license information, please see the LICENSE file.
 * Redistributions of files must retain the above copyright notice.
 *
 * @link http://phpmd.org/
 *
 * @author Manuel Pichler <mapi@phpmd.org>
 * @copyright Manuel Pichler. All rights reserved.
 * @license https://opensource.org/licenses/bsd-license.php BSD License
 */

namespace PHPMD\Renderer;

use PHPMD\AbstractRenderer;
use PHPMD\PHPMD;
use PHPMD\Report;
use PHPMD\RuleViolation;

/**
 * This class will render a JSON report.
 */
class JSONRenderer extends AbstractRenderer
{
    /**
     * {@inheritDoc}
     */
    public function renderReport(Report $report)
    {
        $data     = $this->initReportData();
        $data     = $this->addViolationsToReport($report, $data);
        $data     = $this->addErrorsToReport($report, $data);
        $jsonData = $this->encodeReport($data);

        $writer = $this->getWriter();
        $writer->write($jsonData . PHP_EOL);
    }

    /**
     * Create report data and add renderer meta properties
     *
     * @return array<string, string>
     */
    private function initReportData()
    {
        return [
            'version' => PHPMD::VERSION,
            'package' => 'phpmd',
            'timestamp' => date('c'),
        ];
    }

    /**
     * Add violations, if any, to the report data
     *
     * @param Report               $report The report with potential violations.
     * @param array<string, mixed> $data   The report output to add the violations to.
     *
     * @return array<string, mixed> The report output with violations, if any.
     */
    private function addViolationsToReport(Report $report, array $data)
    {
        $filesList = [];
        /** @var RuleViolation $violation */
        foreach ($report->getRuleViolations() as $violation) {
            $fileName                             = $violation->getFileName();
            $rule                                 = $violation->getRule();
            $filesList[$fileName]['file']         = $fileName;
            $filesList[$fileName]['violations'][] = [
                'beginLine' => $violation->getBeginLine(),
                'endLine' => $violation->getEndLine(),
                'package' => $violation->getNamespaceName(),
                'function' => $violation->getFunctionName(),
                'class' => $violation->getClassName(),
                'method' => $violation->getMethodName(),
                'description' => $violation->getDescription(),
                'rule' => $rule->getName(),
                'ruleSet' => $rule->getRuleSetName(),
                'externalInfoUrl' => $rule->getExternalInfoUrl(),
                'priority' => $rule->getPriority(),
            ];
        }
        $data['files'] = array_values($filesList);

        return $data;
    }

    /**
     * Add errors, if any, to the report data
     *
     * @param Report               $report The report with potential errors.
     * @param array<string, mixed> $data   The report output to add the errors to.
     *
     * @return array<string, mixed> The report output with errors, if any.
     */
    private function addErrorsToReport(Report $report, array $data)
    {
        $errors = $report->getErrors();
        if ($errors) {
            foreach ($errors as $error) {
                $data['errors'][] = [
                    'fileName' => $error->getFile(),
                    'message' => $error->getMessage(),
                ];
            }
        }

        return $data;
    }

    /**
     * Encode report data to the JSON representation string
     *
     * @param array<string, mixed> $data The report data
     *
     * @return string
     */
    private function encodeReport($data)
    {
        $encodeOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_PRETTY_PRINT;

        return json_encode($data, $encodeOptions);
    }
}
