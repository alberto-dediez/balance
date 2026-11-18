Feature:
    I want to see the keyboard shortcuts of the platform and use them

    Scenario: An admin gets the platform shortcuts and the assistant shortcut
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I see a text 'data-controller="keyboard-shortcuts"'
        Then I see a text 'data-combination="ctrl+k"'
        Then I see a text 'data-event="global-search"'
        Then I see a text 'data-combination="ctrl+enter" data-global="1" data-submit="1"'
        Then I see a text 'data-combination="ctrl+f1"'
        Then I see a text 'data-event="help"'
        Then I see a text 'data-combination="a" data-global="0"'
        Then I see a text 'data-event="assistant-toggle"'
        Then I see a text "Enviar formulario"
        Then I see a text "Asistente IA"

    Scenario: An owner does not get the assistant shortcut
        Given user "owner@dediez.software" with role "ROLE_OWNER" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/profile"
        Then the response status should be 200
        Then I see a text 'data-combination="ctrl+f1"'
        Then I do not see a text 'data-combination="a"'
        Then I do not see a text 'data-event="assistant-toggle"'

    Scenario: The search listens to the shortcut event and the profile menu opens the help
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users"
        Then I see a text "keyboard-shortcuts:global-search@window->global-search#open"
        Then I do not see a text "keydown@window->global-search#shortcut"
        Then I see a text 'data-bs-target="#keyboard-shortcuts"'

    Scenario: The help is translated
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/en/admin/users"
        Then the response status should be 200
        Then I see a text "Keyboard shortcuts"
        Then I see a text "Send form"

    Scenario: The register button of a form is sent with Ctrl+Enter
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts/register"
        Then the response status should be 200
        Then I see a text 'data-submit-shortcut="1"'
