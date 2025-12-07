<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Authentication Filter
 * 
 * Ensures user is logged in before accessing protected routes.
 * Redirects to login page if not authenticated.
 * 
 * @package App\Filters
 */
class AuthFilter implements FilterInterface
{
    /**
     * Check if user is authenticated before allowing access
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $user = $session->get('user');

        if (!$user) {
            // Check if this is an API request
            if ($request->isAJAX() || strpos($request->getPath(), 'api/') === 0) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'error'   => 'Unauthorized',
                        'message' => 'Please login to access this resource'
                    ]);
            }

            // Store intended URL for redirect after login
            $session->set('redirect_url', current_url());
            
            return redirect()->to('/login')->with('error', 'Please login to continue');
        }

        // Check role-based access if arguments provided
        if ($arguments !== null) {
            $userRole = $user['role'] ?? 'user';
            
            if (!in_array($userRole, $arguments)) {
                if ($request->isAJAX()) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON([
                            'success' => false,
                            'error'   => 'Forbidden',
                            'message' => 'You do not have permission to access this resource'
                        ]);
                }
                
                return redirect()->to('/dashboard')->with('error', 'Access denied');
            }
        }
    }

    /**
     * Post-processing (not used)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
