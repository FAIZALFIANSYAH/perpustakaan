import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import CategoryForm from '@/Components/Categories/CategoryForm';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('admin.categories.store'));
    };

    return (
        <AdminLayout>
            <CategoryForm
                title="Create Category"
                description="Tambah kategori baru untuk klasifikasi buku."
                submitLabel="Save Category"
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                submit={submit}
                backRoute={route('admin.categories.index')}
            />
        </AdminLayout>
    );
}
