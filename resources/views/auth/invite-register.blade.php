<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Complete seu cadastro</title>
    </head>
    <body>
        <main style="max-width: 480px; margin: 4rem auto; font-family: sans-serif;">
            <h1>Complete seu cadastro</h1>

            <form method="POST" action="{{ route('register.invite.store', ['analyst_id' => $analyst->id, 'email' => $email]) }}">
                @csrf

                <div>
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ $email }}" readonly style="width: 100%; margin-top: .5rem; margin-bottom: 1rem;" />
                </div>

                <div>
                    <label for="name">Nome</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required style="width: 100%; margin-top: .5rem; margin-bottom: 1rem;" />
                </div>

                <div>
                    <label for="tax_id">CPF</label>
                    <input id="tax_id" name="tax_id" type="text" value="{{ old('tax_id') }}" maxlength="11" required style="width: 100%; margin-top: .5rem; margin-bottom: 1rem;" />
                </div>

                <div>
                    <label for="password">Senha</label>
                    <input id="password" name="password" type="password" required style="width: 100%; margin-top: .5rem; margin-bottom: 1rem;" />
                </div>

                <div>
                    <label for="password_confirmation">Confirmar senha</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required style="width: 100%; margin-top: .5rem; margin-bottom: 1rem;" />
                </div>

                @if ($errors->any())
                    <ul style="color: #b91c1c; padding-left: 1.2rem;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <button type="submit" style="width: 100%; padding: .8rem;">Cadastrar</button>
            </form>
        </main>
    </body>
</html>
