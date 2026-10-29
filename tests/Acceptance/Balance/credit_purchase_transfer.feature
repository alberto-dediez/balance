Feature:
    Ability for admins to transfer the unused remainder of a credit lot to another account

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "999" exists
        Given account "ThirdCompany" with id "888" exists
        Given account "FourthCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given european union country "España" with iso "ESP" and vat "21" and id "C-ES" exists
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "100" tonnes exists

    Scenario: As an admin I transfer the remainder and the destination account receives it
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given contribution "C1" of account "666" for project "1111" with "30" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999]" and button "Transferir crédito"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se han transferido 70,000000 t, de las que"
        Then I do not see a text "a 1 cuentas"
        Then account "999" should have "70" available tonnes in project "1111"
        Then account "666" should have "0" available tonnes in project "1111"
        Then credit purchase "2222" should have "30" free tonnes
        Then contribution "C1" should consume "30" tonnes of credit purchase "2222"

    Scenario: The source account no longer sees the transferred remainder as free
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given contribution "C1" of account "666" for project "1111" with "30" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999]" and button "Transferir crédito"
        When I follow redirect
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits"
        Then the response status should be 200
        Then I see a text "Transferido"
        Then I see a text "0,00 t libres"
        Then I do not see a text "70,00 t libres"
        When I go to "/es/credits/purchases/2222"
        Then the response status should be 200
        Then I see a text "Transferido"

    Scenario: The received lot does not claim a request the destination never made
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "999" exists
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given contribution "C1" of account "666" for project "1111" with "30" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999]" and button "Transferir crédito"
        When I follow redirect
        When I am logged as "other@dediez.software"
        When I go to the detail of lot "2" of serial "REF-1"
        Then the response status should be 200
        Then I see a text "Toneladas recibidas"
        Then I see a text "Toneladas del serial"
        Then I do not see a text "Toneladas solicitadas"
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits/purchases/2222"
        Then the response status should be 200
        Then I see a text "Toneladas solicitadas"
        Then I do not see a text "Toneladas recibidas"

    Scenario: The destination account is adhered to the project when it was not
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999]" and button "Transferir crédito"
        When I follow redirect
        Then the response status should be 200
        Then project "1111" should be adhered to account "999"

    Scenario: As an admin I can transfer only part of the remainder
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999]|tonnes:40" and button "Transferir crédito"
        When I follow redirect
        Then the response status should be 200
        Then credit purchase "2222" should have "60" free tonnes
        Then account "999" should have "40" available tonnes in project "1111"

    Scenario: The transfer action is offered from the admin list only on transferable lots
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given credit purchase "4444" for account "666" and project "1111" requesting "20" tonnes exists
        Given credit purchase "4444" is validated with "20" tonnes and reference "REF-2" paid by "paid_by_client"
        Given credit purchase "5555" for account "666" and project "1111" requesting "5" tonnes exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits"
        Then the response status should be 200
        Then I see a text "/es/admin/credits/2222/transfer"
        Then I do not see a text "/es/admin/credits/4444/transfer"
        Then I do not see a text "/es/admin/credits/5555/transfer"

    Scenario: The transfer page shows how much is really available to transfer
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given contribution "C1" of account "666" for project "1111" with "30" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        Then the response status should be 200
        Then I see a text "Disponibles para transferir"
        Then I see a text "70,0000 t"

    Scenario: An exhausted lot redirects to the list instead of showing the form
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given contribution "C1" of account "666" for project "1111" with "100" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        Then I am redirected to route "admin_credit_purchases"
        Then the response status should be 200
        Then I see a text "no tiene crédito disponible para transferir"

    Scenario: As an owner I cannot transfer a credit lot
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        Then the response status should be 403

    Scenario: A purchase paid by the client is not transferable
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_client"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222"
        Then the response status should be 200
        Then I do not see a text "Transferir crédito"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999]" and button "Transferir crédito"
        Then the response status should be 200
        Then I see a text "no se puede transferir"
        Then credit purchase "2222" should have "100" free tonnes

    Scenario: As an admin I split the remainder equally between several accounts
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given contribution "C1" of account "666" for project "1111" with "30" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999,888,777]" and button "Transferir crédito"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "a 3 cuentas"
        Then I see a text "FourthCompany recibe 0,000001 t adicionales"
        Then account "999" should have "23.333333" available tonnes in project "1111"
        Then account "888" should have "23.333333" available tonnes in project "1111"
        Then account "777" should have "23.333334" available tonnes in project "1111"
        Then credit purchase "2222" should have "30" free tonnes
        Then account "666" should have "0" available tonnes in project "1111"

    Scenario: A partial split keeps the indivisible remainder in the source lot
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given contribution "C1" of account "666" for project "1111" with "30" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999,888,777]|tonnes:10" and button "Transferir crédito"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "sin repartir en el lote de origen"
        Then account "999" should have "3.333333" available tonnes in project "1111"
        Then account "888" should have "3.333333" available tonnes in project "1111"
        Then account "777" should have "3.333333" available tonnes in project "1111"
        Then account "666" should have "60.000001" available tonnes in project "1111"

    Scenario: As an admin I split the requested tonnes between several accounts
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999,888]|tonnes:40" and button "Transferir crédito"
        When I follow redirect
        Then the response status should be 200
        Then account "999" should have "20" available tonnes in project "1111"
        Then account "888" should have "20" available tonnes in project "1111"
        Then credit purchase "2222" should have "60" free tonnes

    Scenario: Two successive transfers from the same lot give the second account only what it received
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[888]|tonnes:40" and button "Transferir crédito"
        When I follow redirect
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999]|tonnes:20" and button "Transferir crédito"
        When I follow redirect
        Then the response status should be 200
        Then account "888" should have "40" available tonnes in project "1111"
        Then account "999" should have "20" available tonnes in project "1111"
        Then credit purchase "2222" should have "40" free tonnes

    Scenario: The contributions of the second account consume its own range of the serial
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[888]|tonnes:40" and button "Transferir crédito"
        When I follow redirect
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999]|tonnes:20" and button "Transferir crédito"
        When I follow redirect
        Given contribution "C2" of account "999" for project "1111" with "20" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C2" succeeded
        Then contribution "C2" should consume the range "40" to "60" of serial "REF-1"

    Scenario: The source account is not offered as a destination
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        Then the response status should be 200
        Then I see an option "OtherCompany" in form field "destinationAccountIds"
        Then I do not see an option "MyCompany" in form field "destinationAccountIds"

    Scenario: The credit alerts of an account are measured on the tonnes it received
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "999" exists
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[888]|tonnes:40" and button "Transferir crédito"
        When I follow redirect
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999]|tonnes:20" and button "Transferir crédito"
        When I follow redirect
        Given contribution "C2" of account "999" for project "1111" with "16" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C2" succeeded
        When the credit alerts are checked
        Then user "other@dediez.software" should have 1 internal notifications

    Scenario: A received lot that has not ceded anything does not show it as transferred
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "999" exists
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[888]|tonnes:40" and button "Transferir crédito"
        When I follow redirect
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999]|tonnes:20" and button "Transferir crédito"
        When I follow redirect
        When I am logged as "other@dediez.software"
        When I go to the detail of lot "3" of serial "REF-1"
        Then the response status should be 200
        Then I see a text "Toneladas recibidas"
        Then I see a text "20,0000 t"
        Then I see a text "Toneladas del serial"
        Then I see a text "100,0000 t"
        Then I see a text "Otros lotes del serial"
        Then I do not see a text "Transferido"

    Scenario: Forcing the source account as a destination cancels the whole split
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I pick "666" in the autocomplete "destinationAccountIds]["
        When I submit a form with "destinationAccountIds:[666,999]" and button "Transferir crédito"
        Then the response status should be 200
        Then I see a text "La opción seleccionada no es válida"
        Then credit purchase "2222" should have "100" free tonnes
        Then account "999" should have "0" available tonnes in project "1111"
