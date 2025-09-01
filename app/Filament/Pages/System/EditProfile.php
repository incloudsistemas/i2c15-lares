<?php

namespace App\Filament\Pages\System;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str;

class EditProfile extends BaseEditProfile
{
    protected static ?string $slug = 'my-profile';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Nome'))
                    ->required()
                    ->minLength(2)
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpanFull(),
                $this->getPasswordFormComponent()
                    ->helperText(__('Preencha apenas se desejar alterar a senha. Min. de 8 dígitos.'))
                    ->minLength(8)
                    ->maxLength(255),
                $this->getPasswordConfirmationFormComponent(),
                Forms\Components\SpatieMediaLibraryFileUpload::make('avatar')
                    ->label(__('Avatar'))
                    ->helperText(__('Tipos de arquivo permitidos: .png, .jpg, .jpeg, .gif. // 500x500px // máx. 5 mb.'))
                    ->collection('avatar')
                    ->image()
                    ->avatar()
                    ->downloadable()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        // '16:9', // ex: 1920x1080px
                        // '4:3',  // ex: 1024x768px
                        '1:1',  // ex: 500x500px
                    ])
                    ->circleCropper()
                    ->imageResizeTargetWidth(500)
                    ->imageResizeTargetHeight(500)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/gif'])
                    ->maxSize(5120)
                    ->getUploadedFileNameForStorageUsing(
                        fn(TemporaryUploadedFile $file, Get $get): string =>
                        (string) str('-' . md5(uniqid()) . '-' . time() . '.' . $file->guessExtension())
                            ->prepend(Str::slug($get('name'))),
                    )
                    ->columnSpanFull(),
            ]);
    }
}
