<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api\Validator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AnalyticsRequestValidator
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function validateOverviewRequest(Request $request): array
    {

        $days = $request->query->get('days', 30);
        
        $constraints = new Assert\Collection([
            'days' => [
                new Assert\Type(['type' => 'integer', 'message' => 'Days must be an integer.']),
                new Assert\Range([
                    'min' => 1,
                    'max' => 365,
                    'minMessage' => 'Days must be at least {{ limit }}.',
                    'maxMessage' => 'Days cannot be more than {{ limit }}.',
                    'notInRangeMessage' => 'Days must be between {{ min }} and {{ max }}.',
                ]),
            ],
        ]);

        $violations = $this->validator->validate(['days' => (int)$days], $constraints);

        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'property' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $errors;
    }

    public function validatePopularRequest(Request $request): array
    {
        $limit = $request->query->get('limit', 10);

        $constraints = new Assert\Collection([
            'limit' => [
                new Assert\Type(['type' => 'integer', 'message' => 'Limit must be an integer.']),
                new Assert\Range([
                    'min' => 1,
                    'max' => 100,
                    'minMessage' => 'Limit must be at least {{ limit }}.',
                    'maxMessage' => 'Limit cannot be more than {{ limit }}.',
                    'notInRangeMessage' => 'Limit must be between {{ min }} and {{ max }}.',
                ]),
            ],
        ]);

        $violations = $this->validator->validate(['limit' => (int)$limit], $constraints);

        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'property' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $errors;
    }
}