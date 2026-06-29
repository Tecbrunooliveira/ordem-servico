<?php

namespace App\Services;

class CnpjaOpenService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     nome: string,
     *     documento: string,
     *     email: string,
     *     telefone: string,
     *     cidade: string,
     *     estado: string,
     *     rua: string,
     *     numero: string,
     *     bairro: string
     * }
     */
    public function mapFromResponse(array $data): array
    {
        $address = is_array($data['address'] ?? null) ? $data['address'] : [];
        $company = is_array($data['company'] ?? null) ? $data['company'] : [];
        $phones = is_array($data['phones'] ?? null) ? $data['phones'] : [];
        $emails = is_array($data['emails'] ?? null) ? $data['emails'] : [];

        $taxId = (string) ($data['taxId'] ?? '');

        return [
            'nome' => (string) ($company['name'] ?? $data['alias'] ?? ''),
            'documento' => $this->formatCnpj($taxId),
            'email' => (string) ($emails[0]['address'] ?? ''),
            'telefone' => $this->formatPhone(is_array($phones[0] ?? null) ? $phones[0] : []),
            'cidade' => (string) ($address['city'] ?? ''),
            'estado' => (string) ($address['state'] ?? ''),
            'rua' => (string) ($address['street'] ?? ''),
            'numero' => (string) ($address['number'] ?? ''),
            'bairro' => (string) ($address['district'] ?? ''),
        ];
    }

    private function formatCnpj(string $taxId): string
    {
        $taxId = preg_replace('/\D/', '', $taxId);

        if (strlen($taxId) !== 14) {
            return $taxId;
        }

        return preg_replace(
            '/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/',
            '$1.$2.$3/$4-$5',
            $taxId,
        ) ?? $taxId;
    }

    /**
     * @param  array<string, mixed>  $phone
     */
    private function formatPhone(array $phone): string
    {
        $area = preg_replace('/\D/', '', (string) ($phone['area'] ?? ''));
        $number = preg_replace('/\D/', '', (string) ($phone['number'] ?? ''));

        if ($area === '' || $number === '') {
            return '';
        }

        if (strlen($number) === 9) {
            return sprintf('(%s) %s-%s', $area, substr($number, 0, 5), substr($number, 5));
        }

        if (strlen($number) === 8) {
            return sprintf('(%s) %s-%s', $area, substr($number, 0, 4), substr($number, 4));
        }

        return "({$area}) {$number}";
    }
}
