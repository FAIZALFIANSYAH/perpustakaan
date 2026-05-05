import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import CategoryForm from '@/Components/Categories/CategoryForm';
import LibrarianLayout from '@/Layouts/LibrarianLayout';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('librarian.categories.store'));
    };

    return (
        <LibrarianLayout>
            <CategoryForm
                title="Create Category"
                description="Tambah kategori baru untuk klasifikasi buku."
                submitLabel="Save Category"
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                submit={submit}
                backRoute={route('librarian.categories.index')}
            />
        </LibrarianLayout>
    );
}
