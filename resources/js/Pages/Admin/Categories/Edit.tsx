import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import CategoryForm from '@/Components/Categories/CategoryForm';
import AdminLayout from '@/Layouts/AdminLayout';

type Category = {
    id: number;
    name: string;
};

type Props = {
    category: Category;
};

export default function Edit({ category }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: category.name ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        put(route('admin.categories.update', category.id));
    };

    return (
        <AdminLayout>
            <CategoryForm
                title="Edit Category"
                description="Perbarui nama kategori."
                submitLabel="Update Category"
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
