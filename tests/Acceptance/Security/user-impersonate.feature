Feature:
    Ability to impersonate other users as super admin

    Scenario: As super admin I see the impersonate action in the users list
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I see a text "_switch_user=user1@dediez.software"

    Scenario: As admin I do not see the impersonate action in the users list
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I do not see a text "_switch_user"

    Scenario: As super admin I can impersonate another user and it is recorded
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/?_switch_user=user1@dediez.software"
        When I go to "/es/profile"
        When I submit a form with "preferredLocale:en" and button "Guardar"
        Then the recorded events of "user1@dediez.software" are impersonated by "superadmin@dediez.software"

    Scenario: While impersonating I see the link to go back to my user
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/?_switch_user=user1@dediez.software"
        When I go to "/es/profile"
        Then the response status should be 200
        Then I see a text "Volver a mi usuario"
        Then I see a text "Suplantando a"
        Then I see a text "_switch_user=_exit"

    Scenario: Without impersonating I do not see the link to go back to my user
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/profile"
        Then the response status should be 200
        Then I do not see a text "Volver a mi usuario"

    Scenario: I can go back to my user after impersonating
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/?_switch_user=user1@dediez.software"
        When I go to "/es/?_switch_user=_exit"
        When I go to "/es/profile"
        Then the response status should be 200
        Then I do not see a text "Volver a mi usuario"

    Scenario: As admin I cannot impersonate another user
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/?_switch_user=user1@dediez.software"
        Then the response status should be 403

    Scenario: Events performed without impersonation are not marked as impersonated
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/profile"
        When I submit a form with "preferredLocale:en" and button "Guardar"
        Then no recorded event is impersonated
