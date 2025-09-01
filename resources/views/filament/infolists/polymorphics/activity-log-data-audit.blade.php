@if (empty($changes))
    <p class="italic text-gray-500">— Nenhuma alteração registrada —</p>
@else
    <div class="w-full">
        <table class="table-fixed w-full border border-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-1/3 border px-4 py-2 text-left text-sm font-medium">Campo</th>
                    <th class="w-1/3 border px-4 py-2 text-left text-sm font-medium">Valor Atual</th>
                    <th class="w-1/3 border px-4 py-2 text-left text-sm font-medium">Valor Anterior</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @foreach ($changes as $data)
                    <tr>
                        <td class="w-1/3 border px-4 py-2 text-sm font-semibold break-words whitespace-normal align-top">
                            {{ $data['label'] ?? '—' }}
                        </td>
                        <td class="w-1/3 border px-4 py-2 text-sm break-words whitespace-normal align-top">
                            {!! $data['attr'] ?? '—' !!}
                        </td>
                        <td class="w-1/3 border px-4 py-2 text-sm break-words whitespace-normal align-top">
                            {!! $data['old'] ?? '—' !!}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
