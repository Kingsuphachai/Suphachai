<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">การแจ้งเตือน</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-4">
                <form method="POST" action="{{ route('admin.notifications.read_all') }}" class="mb-4">
                    @csrf
                </form>

                <div class="divide-y">
                    @forelse($notifications as $n)
                        <div class="py-3 flex items-start gap-3 {{ $n->read_at ? 'opacity-70' : '' }}">
                            <div>🔔</div>
                            <div class="flex-1">
                                <div class="font-medium">{{ data_get($n->data, 'title') }}</div>
                                <div class="text-sm text-gray-600">{{ data_get($n->data, 'body') }}</div>
                                @if($url = data_get($n->data, 'url'))
                                    <a href="{{ $url }}" class="text-indigo-600 text-sm underline">เปิดลิงก์</a>
                                @endif
                                <div class="text-xs text-gray-400">{{ $n->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-gray-500">ยังไม่มีการแจ้งเตือน</div>
                    @endforelse
                </div>

                <div class="mt-4">{{ $notifications->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>