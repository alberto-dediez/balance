Feature:
    Ability to read a guide article in web

    Scenario: As admin I open an article and see its title and body
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given guide article "seguridad/organizacion/usuarios" for profile "admin" with title "Usuarios" and body:
            """
            # Usuarios

            Aquí se registran los usuarios de la plataforma.
            """
        When I am logged as "admin@dediez.software"
        When I go to "/es/guide/seguridad/organizacion/usuarios"
        Then the response status should be 200
        Then I see a text "Usuarios"
        Then I see a text "Aquí se registran los usuarios de la plataforma."

    Scenario: As owner I see the article of my own profile
        Given user "owner@dediez.software" with role "ROLE_OWNER" exists
        Given guide article "seguridad/organizacion/usuarios" for profile "admin" with title "Usuarios de admin" and body:
            """
            Texto para administradores.
            """
        Given guide article "seguridad/organizacion/usuarios" for profile "owner" with title "Usuarios de owner" and body:
            """
            Texto para propietarios.
            """
        When I am logged as "owner@dediez.software"
        When I go to "/es/guide/seguridad/organizacion/usuarios"
        Then the response status should be 200
        Then I see a text "Texto para propietarios."
        Then I do not see a text "Texto para administradores."

    Scenario: An article that only exists for another profile returns 404
        Given user "owner@dediez.software" with role "ROLE_OWNER" exists
        Given guide article "seguridad/organizacion/usuarios" for profile "admin" with title "Usuarios" and body:
            """
            Texto para administradores.
            """
        When I am logged as "owner@dediez.software"
        When I go to "/es/guide/seguridad/organizacion/usuarios"
        Then the response status should be 404

    Scenario: An unknown article returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/guide/no/existe"
        Then the response status should be 404

    Scenario: An anonymous visitor cannot read an article
        When I go to "/es/guide/seguridad/organizacion/usuarios"
        Then the response status should be 403

    Scenario: A related article is linked
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given guide article "seguridad/organizacion/usuarios" for profile "admin" with title "Usuarios" and body:
            """
            # Usuarios

            ## Relacionado

            - [Gestión de cuentas](cuentas.md)
            """
        Given guide article "seguridad/organizacion/cuentas" for profile "admin" with title "Gestión de cuentas" and body:
            """
            # Cuentas
            """
        When I am logged as "admin@dediez.software"
        When I go to "/es/guide/seguridad/organizacion/usuarios"
        Then the response status should be 200
        Then I see a link "Gestión de cuentas" to "/es/guide/seguridad/organizacion/cuentas"

    Scenario: The fragment for the modal is returned for an XHR request
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given guide article "seguridad/organizacion/usuarios" for profile "admin" with title "Usuarios" and body:
            """
            Texto del artículo.
            """
        When I am logged as "admin@dediez.software"
        When I go to "/es/guide/seguridad/organizacion/usuarios" as an ajax request
        Then the response status should be 200
        Then I see a text "modal-header"
        Then I see a text "Texto del artículo."
        Then I do not see a text "main-wrapper"
