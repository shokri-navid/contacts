<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Contacts\Service;

use OCA\Contacts\AppInfo\Application;
use OCA\Contacts\Service\Social\CompositeSocialProvider;

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\ContactsManager;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Contacts\IManager;
use OCP\Http\Client\IClientService;
use OCP\IAddressBook;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Mail\IMailer;


class InvitationApiService {
	private $appName;
	private $invitation_table_name = "invites";
	public function __construct(
		private IDBConnection $dbConnection,
		private IManager $manager,
		private IConfig $config,
		private IClientService $clientService,
		private IL10N $l10n,
		private IURLGenerator $urlGen,
		private CardDavBackend $davBackend,
		private ITimeFactory $timeFactory,
		private IMailer $mailer
	) {
		$this->appName = Application::APP_ID;
	}

	public function createInvitation(Invitation $invitation) : array{
		$query = $this->dbConnection->getQueryBuilder();
		$token = ''; //todo: creat guid
		$query->insert($this->invitation_table_name)
		->values([
			'user_id' => $invitation->userId,
			'token' => $token,
			'email' => $invitation->email,
			'createdAt'=> $invitation->createdAt,
			'expiresAt' => $invitation->expiresAt,
		])->executeStatement();
		
		sendInvitationEMail($invitation->email, $invitation->name, $token);



	}
	
	public function findInvitation(string $token){
		$query = $this->dbConnection->getQueryBuilder();
		
		$qb->select('*')
		->from($this->invitation_table_name)
		->where(
			$qb->expr()->eq('token', $qb->createNamedParameter($token, IQueryBuilder::PARAM_STR))
		);

	 $result = $qb->executeQuery();
	 $row = $result->fetchAssociative();
	 $result->closeCursor();

	 return $row;
		
	}

	public function acceptInvitation(InvitationAcceptRequestDto $request, int $ownerId, \Datetime $now) : array{
		$query = $this->dbConnection->getQueryBuilder();
		
		$qb->update($this->invitation_table_name)
				->set('accepted', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
				->set('acceptedAt', $qb->createNamedParameter($now, IQueryBuilder::PARAM_STR))
		->where(
			$qb->expr()->eq('token', $qb->createNamedParameter($token, IQueryBuilder::PARAM_STR))
		);
	}

	public function sendInvitationEMail($toUserEmail, $toDisplayName, $token) {

		if (!$toUserEmail || !filter_var($toUserEmail, FILTER_VALIDATE_EMAIL)) {
				throw new Exception('Invalid email address (' . $toUserEmail . ')');
		}
	   
		$emailTemplate = $this->mailer->createEMailTemplate('test@gmail.com', [
								'owner' => 'tester',
								'title' => 'Assign Value',
								'link' => 'http://google.com'
						]);
	
	   $emailTemplate->setSubject('Invitation to the NextCloud contacts');
						$emailTemplate->addHeader();
						$emailTemplate->addHeading('OCM Invitation', false);
						$emailTemplate->addBodyText('it is an ocm invitation.');
						$emailTemplate->addBodyText('please click below link to be added as contact');
						$emailTemplate->addBodyText('Updated results');
	
	   //$emailTemplate->addFooter('This email is sent to you on behalf of the application. If you want to get removed from this app, contact the site administrator');
	
		try {
				$message = $this->mailer->createMessage();
				$message->setTo([$toUserEmail => $toDisplayName]);
				$message->useTemplate($emailTemplate);
				$this->mailer->send($message);
	
				return 1;
	
		} catch (\Exception $e) {
				$this->logger->logException($e);
				throw $e;
		}
	}
}