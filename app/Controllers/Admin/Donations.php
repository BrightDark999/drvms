<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\Traits\CrudResponses;
use App\Models\DonationModel;
use App\Models\DonorModel;
use App\Services\DonationService;
use App\Validation\DonationRules;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class Donations extends BaseController
{
    use CrudResponses;

    public function index(): string
    {
        return view('admin/donations/index', [
            'title'   => 'Donations & Assistance',
            'drms'    => drms_config(),
            'dataUrl' => base_url('admin/donations/data'),
            'donors'  => model(DonorModel::class)->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function data(): ResponseInterface
    {
        helper('datatable');
        $request = $this->request;
        $start   = max(0, (int) $request->getVar('start'));
        $length  = min(100, max(5, (int) ($request->getVar('length') ?: 10)));

        $model   = model(DonationModel::class);
        $total   = $model->countAllResults();
        $builder = $model->builder();
        $builder->select('donations.*, donors.name AS donor_name')
            ->join('donors', 'donors.id = donations.donor_id', 'left');

        $rows = $builder->orderBy('donations.id', 'DESC')->limit($length, $start)->get()->getResultArray();

        $data = [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            $data[] = [
                esc($r['donor_name'] ?? '—'),
                esc(ucfirst($r['donation_type'] ?? '')),
                $r['amount_peso'] !== null ? '₱ ' . number_format((float) $r['amount_peso'], 2) : '—',
                esc($r['received_at'] ?? '—'),
                drms_table_actions($id),
            ];
        }

        return datatable_response($request, $data, $total, $total);
    }

    public function show(int $id): ResponseInterface
    {
        $row = model(DonationModel::class)->find($id);
        if (! $row) {
            return $this->crudFail('Donation not found.', 404);
        }

        if (! empty($row['donor_id'])) {
            $donor = model(DonorModel::class)->find((int) $row['donor_id']);
            if ($donor) {
                $row['donor_name'] = $donor['name'];
            }
        }

        return $this->crudOk('OK', $row);
    }

    public function store(): ResponseInterface
    {
        if (! $this->validate(DonationRules::save())) {
            return $this->crudValidationFail();
        }

        try {
            $row = (new DonationService())->create($this->request->getPost(), auth()->id());

            return $this->crudOk('Donation recorded.', $row);
        } catch (RuntimeException $e) {
            return $this->crudFail($e->getMessage(), 500);
        }
    }

    public function update(int $id): ResponseInterface
    {
        if (! $this->validate(DonationRules::save())) {
            return $this->crudValidationFail();
        }

        if (! (new DonationService())->update($id, $this->request->getPost())) {
            return $this->crudFail('Could not update donation.', 400);
        }

        return $this->crudOk('Donation updated.');
    }

    public function delete(int $id): ResponseInterface
    {
        if (! (new DonationService())->delete($id)) {
            return $this->crudFail('Could not delete donation.', 400);
        }

        return $this->crudOk('Donation deleted.');
    }
}
